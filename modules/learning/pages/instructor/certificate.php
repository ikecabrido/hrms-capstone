<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$templates = [];
$issuedCertificates = [];
$userCerts = [];
$courses = [];
$stats = ['total' => 0, 'active' => 0, 'archived' => 0, 'expiring_30' => 0, 'expiring_7' => 0, 'expired' => 0, 'valid' => 0];
$expiryData = [];

try {
    $pdo = (new Database())->getConnection();

    $templates = $pdo->query(
        "SELECT ct.id, ct.title, ct.description, ct.is_active, ct.created_at, ct.updated_at,
                c.title AS course_title
         FROM ld_certificate_template ct
         LEFT JOIN ld_course c ON c.id = ct.course_id
         WHERE ct.is_active = 1
         ORDER BY ct.created_at DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $issuedCertificates = $pdo->query(
        "SELECT c.id, c.learner_id, c.course_id, c.verification_code, c.issued_at, c.valid_until, c.status,
                co.title AS course_title,
                emp.first_name, emp.last_name, emp.email
         FROM ld_certificate c
         JOIN ld_course co ON co.id = c.course_id
         JOIN em_employees emp ON emp.employee_id = c.learner_id
         ORDER BY c.issued_at DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Users ranked by certificate count
    $userCerts = $pdo->query(
        "SELECT emp.employee_id, emp.first_name, emp.last_name, emp.email,
                COUNT(c.id) AS cert_count,
                SUM(CASE WHEN c.status = 'active' THEN 1 ELSE 0 END) AS active_count,
                MAX(c.issued_at) AS last_cert_date
         FROM em_employees emp
         JOIN ld_certificate c ON c.learner_id = emp.employee_id
         GROUP BY emp.employee_id, emp.first_name, emp.last_name, emp.email
         ORDER BY cert_count DESC, last_cert_date DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $statsRow = $pdo->query(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) AS archived
         FROM ld_certificate"
    )->fetch(PDO::FETCH_ASSOC);
    $stats['total'] = (int)($statsRow['total'] ?? 0);
    $stats['active'] = (int)($statsRow['active'] ?? 0);
    $stats['archived'] = (int)($statsRow['archived'] ?? 0);

    // Compute expiry stats and data
    $now = time();
    foreach ($issuedCertificates as &$cert) {
        $validUntil = $cert['valid_until'] ? strtotime($cert['valid_until']) : null;
        $daysLeft = $validUntil ? (int)ceil(($validUntil - $now) / 86400) : null;
        $cert['days_left'] = $daysLeft;

        if ($cert['status'] !== 'active') {
            $cert['expiry_status'] = 'archived';
        } elseif ($daysLeft === null) {
            $cert['expiry_status'] = 'valid';
            $stats['valid']++;
        } elseif ($daysLeft <= 0) {
            $cert['expiry_status'] = 'expired';
            $stats['expired']++;
        } elseif ($daysLeft <= 7) {
            $cert['expiry_status'] = 'critical';
            $stats['expiring_7']++;
        } elseif ($daysLeft <= 30) {
            $cert['expiry_status'] = 'warning';
            $stats['expiring_30']++;
        } else {
            $cert['expiry_status'] = 'valid';
            $stats['valid']++;
        }

        $expiryData[] = $cert;
    }
    unset($cert);

    $courses = $pdo->query(
        "SELECT id, title FROM ld_course ORDER BY title"
    )->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $templates = [];
    $issuedCertificates = [];
    $userCerts = [];
    $courses = [];
    $expiryData = [];
}

function certTimeAgo($dt) {
    if (!$dt) return 'N/A';
    $d = time() - strtotime($dt);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    if ($d < 604800) return floor($d / 86400) . 'd ago';
    return date('M j, Y', strtotime($dt));
}
?>
<style>
.expiry-table { width: 100%; border-collapse: separate; border-spacing: 0; background: var(--surface, #fff); border-radius: 12px; overflow: hidden; border: 1px solid rgba(32,0,130,0.08); }
.expiry-table th { padding: 0.75rem 1rem; text-align: left; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--primary); background: rgba(32,0,130,0.03); border-bottom: 1px solid rgba(32,0,130,0.08); }
.expiry-table td { padding: 0.75rem 1rem; font-size: 0.85rem; border-bottom: 1px solid rgba(32,0,130,0.05); color: var(--text, #333); }
.expiry-table tr:last-child td { border-bottom: none; }
.expiry-table tr:hover td { background: rgba(32,0,130,0.02); }
.expiry-badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.72rem; font-weight: 700; }
.expiry-badge.valid { background: rgba(16,185,129,0.1); color: #10b981; }
.expiry-badge.warning { background: rgba(245,158,11,0.1); color: #d97706; }
.expiry-badge.critical { background: rgba(220,53,69,0.1); color: #dc3545; }
.expiry-badge.expired { background: rgba(220,53,69,0.15); color: #dc3545; }
</style>
<div class="module-content">
    <div class="toolbar">
        <div class="toolbar-search">
            <input type="search" id="cert-search" placeholder="Search certificates, users, or templates..." aria-label="Search" />
        </div>
        <div class="toolbar-actions">
            <select class="toolbar-filter" id="cert-status-filter" aria-label="Filter by status">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="archived">Archived</option>
            </select>
        </div>
    </div>

    <!-- Stats -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:0.75rem; margin-bottom:1.5rem;">
        <div style="padding:1rem 1.15rem; border-radius:12px; border:1.5px solid rgba(32,0,130,0.15); background:rgba(32,0,130,0.03); text-align:center;">
            <div style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Total</div>
            <div style="font-size:1.6rem; font-weight:800; color:var(--text); margin-top:0.2rem;"><?= $stats['total'] ?></div>
        </div>
        <div style="padding:1rem 1.15rem; border-radius:12px; border:1.5px solid rgba(16,185,129,0.2); background:rgba(16,185,129,0.03); text-align:center;">
            <div style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; color:#10b981; font-weight:700;">Active</div>
            <div style="font-size:1.6rem; font-weight:800; color:var(--text); margin-top:0.2rem;"><?= $stats['active'] ?></div>
        </div>
        <div style="padding:1rem 1.15rem; border-radius:12px; border:1.5px solid rgba(245,158,11,0.2); background:rgba(245,158,11,0.03); text-align:center;">
            <div style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; color:#d97706; font-weight:700;">Expiring 30d</div>
            <div style="font-size:1.6rem; font-weight:800; color:var(--text); margin-top:0.2rem;"><?= $stats['expiring_30'] ?></div>
        </div>
        <div style="padding:1rem 1.15rem; border-radius:12px; border:1.5px solid rgba(220,53,69,0.2); background:rgba(220,53,69,0.03); text-align:center;">
            <div style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; color:#dc3545; font-weight:700;">Expiring 7d</div>
            <div style="font-size:1.6rem; font-weight:800; color:var(--text); margin-top:0.2rem;"><?= $stats['expiring_7'] ?></div>
        </div>
        <div style="padding:1rem 1.15rem; border-radius:12px; border:1.5px solid rgba(239,68,68,0.2); background:rgba(239,68,68,0.03); text-align:center;">
            <div style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; color:#ef4444; font-weight:700;">Revoked</div>
            <div style="font-size:1.6rem; font-weight:800; color:var(--text); margin-top:0.2rem;"><?= $stats['archived'] ?></div>
        </div>
    </div>

    <div class="tab-container">
        <div class="tab-list">
            <button type="button" class="tab-item active" data-tab="tab-certificates">Certificates (<?= count($issuedCertificates) ?>)</button>
            <button type="button" class="tab-item" data-tab="tab-expiry">Expiry (<?= $stats['expiring_30'] + $stats['expiring_7'] + $stats['expired'] ?>)</button>
            <button type="button" class="tab-item" data-tab="tab-users">Users (<?= count($userCerts) ?>)</button>
            <button type="button" class="tab-item" data-tab="tab-templates">Templates (<?= count($templates) ?>)</button>
        </div>

        <!-- Tab 1: Certificates (Card View) -->
        <div class="tab-content active" data-tab="tab-certificates">
            <?php if (empty($issuedCertificates)): ?>
                <div class="mode-card" style="text-align:center; padding:2rem;">
                    <div style="font-size:3rem; opacity:0.2; color:var(--primary); margin-bottom:1rem;"><i class="fas fa-award"></i></div>
                    <h3 style="color:var(--text);">No Certificates Issued</h3>
                    <p style="color:rgba(32,0,130,0.5);">Certificates will appear here once learners complete their courses.</p>
                </div>
            <?php else: ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1.25rem;">
                    <?php foreach ($issuedCertificates as $cert):
                        $name = htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']);
                        $initials = strtoupper(substr($cert['first_name'], 0, 1) . substr($cert['last_name'], 0, 1));
                        $isExpired = $cert['valid_until'] && strtotime($cert['valid_until']) < time();
                        $daysLeft = $cert['days_left'] ?? null;
                        $statusColor = $cert['expiry_status'] === 'critical' ? '#dc3545' : ($cert['expiry_status'] === 'warning' ? '#d97706' : ($isExpired ? '#ef4444' : ($cert['status'] === 'active' ? '#10b981' : '#f59e0b')));
                        $statusLabel = $isExpired ? 'Expired' : ucfirst($cert['status']);
                        $expiryBadge = '';
                        if ($cert['status'] === 'active' && $daysLeft !== null) {
                            if ($daysLeft <= 0) {
                                $expiryBadge = '<span style="margin-top:0.3rem;padding:0.15rem 0.5rem;border-radius:999px;font-size:0.65rem;font-weight:700;background:rgba(220,53,69,0.1);color:#dc3545;">Expired</span>';
                            } elseif ($daysLeft <= 7) {
                                $expiryBadge = '<span style="margin-top:0.3rem;padding:0.15rem 0.5rem;border-radius:999px;font-size:0.65rem;font-weight:700;background:rgba(220,53,69,0.1);color:#dc3545;">' . $daysLeft . 'd left</span>';
                            } elseif ($daysLeft <= 30) {
                                $expiryBadge = '<span style="margin-top:0.3rem;padding:0.15rem 0.5rem;border-radius:999px;font-size:0.65rem;font-weight:700;background:rgba(245,158,11,0.1);color:#d97706;">' . $daysLeft . 'd left</span>';
                            }
                        }
                    ?>
                        <div class="cert-card-item" data-search="<?= strtolower($name . ' ' . $cert['email'] . ' ' . $cert['course_title']) ?>" data-status="<?= $cert['status'] ?>" data-expiry="<?= $cert['expiry_status'] ?>" style="border:2px solid rgba(16,185,129,0.15); border-radius:14px; overflow:hidden; transition:transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
                            <div style="background:linear-gradient(135deg, rgba(16,185,129,0.1), rgba(52,211,153,0.06)); padding:1.25rem; text-align:center; border-bottom:1px solid rgba(16,185,129,0.12);">
                                <div style="font-size:2rem; color:#10b981; margin-bottom:0.4rem;"><i class="fas fa-award"></i></div>
                                <div style="font-weight:700; color:var(--text); font-size:1rem;"><?= htmlspecialchars($cert['course_title']) ?></div>
                                <div style="display:flex; flex-direction:column; align-items:center;">
                                    <span style="display:inline-block; margin-top:0.4rem; padding:0.2rem 0.6rem; border-radius:999px; font-size:0.7rem; font-weight:700; background:<?= $statusColor ?>15; color:<?= $statusColor ?>;"><?= $statusLabel ?></span>
                                    <?= $expiryBadge ?>
                                </div>
                            </div>
                            <div style="padding:1rem 1.25rem;">
                                <div style="display:flex; align-items:center; gap:0.65rem; margin-bottom:0.75rem;">
                                    <div style="width:36px; height:36px; min-width:36px; border-radius:50%; background:linear-gradient(135deg, rgba(16,185,129,0.9), rgba(52,211,153,0.75)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.75rem;"><?= $initials ?></div>
                                    <div>
                                        <div style="font-weight:700; color:var(--text); font-size:0.9rem;"><?= $name ?></div>
                                        <div style="font-size:0.75rem; color:rgba(32,0,130,0.45);"><?= htmlspecialchars($cert['email']) ?></div>
                                    </div>
                                </div>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; font-size:0.8rem; margin-bottom:0.75rem;">
                                    <div>
                                        <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--primary); font-weight:700;">Issued</div>
                                        <div style="color:rgba(32,0,130,0.6); margin-top:0.1rem;"><?= $cert['issued_at'] ? date('M j, Y', strtotime($cert['issued_at'])) : 'N/A' ?></div>
                                    </div>
                                    <div>
                                        <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--primary); font-weight:700;">Valid Until</div>
                                        <div style="color:rgba(32,0,130,0.6); margin-top:0.1rem;"><?= $cert['valid_until'] ? date('M j, Y', strtotime($cert['valid_until'])) : 'No expiry' ?></div>
                                    </div>
                                </div>
                                <div style="font-family:monospace; font-size:0.72rem; color:rgba(32,0,130,0.5); background:rgba(32,0,130,0.04); padding:0.5rem 0.7rem; border-radius:6px; margin-bottom:0.75rem; word-break:break-all;">
                                    <?= htmlspecialchars($cert['verification_code']) ?>
                                </div>
                                <div style="display:flex; gap:0.5rem;">
                                    <a href="?page=public/verify-certificate&code=<?= htmlspecialchars($cert['verification_code']) ?>" target="_blank" style="flex:1; text-align:center; padding:0.5rem; border-radius:8px; font-size:0.8rem; font-weight:700; background:var(--primary); color:var(--surface); text-decoration:none;">View</a>
                                    <?php if ($cert['status'] === 'active'): ?>
                                    <button class="cert-extend-btn" data-id="<?= $cert['id'] ?>" data-course="<?= htmlspecialchars($cert['course_title'], ENT_QUOTES) ?>" data-current="<?= $cert['valid_until'] ?? '' ?>" onclick="openExtendModal(this)" style="padding:0.5rem; border-radius:8px; font-size:0.8rem; font-weight:700; background:rgba(16,185,129,0.1); color:#10b981; border:none; cursor:pointer;"><i class="fas fa-clock"></i></button>
                                    <button class="cert-revoke-btn" data-id="<?= $cert['id'] ?>" data-course="<?= htmlspecialchars($cert['course_title'], ENT_QUOTES) ?>" onclick="confirmRevoke(this)" style="padding:0.5rem; border-radius:8px; font-size:0.8rem; font-weight:700; background:rgba(239,68,68,0.1); color:#ef4444; border:none; cursor:pointer;"><i class="fas fa-ban"></i></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 2: Expiry Dashboard -->
        <div class="tab-content" data-tab="tab-expiry">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; gap:0.5rem; flex-wrap:wrap;">
                <select id="expiry-filter" class="toolbar-filter" aria-label="Filter by expiry status">
                    <option value="all">All Certificates (<?= count($expiryData) ?>)</option>
                    <option value="warning">Expiring 30d (<?= $stats['expiring_30'] ?>)</option>
                    <option value="critical">Expiring 7d (<?= $stats['expiring_7'] ?>)</option>
                    <option value="expired">Expired (<?= $stats['expired'] ?>)</option>
                    <option value="valid">Valid (<?= $stats['valid'] ?>)</option>
                </select>
            </div>
            <?php if (empty($expiryData)): ?>
                <div class="mode-card" style="text-align:center; padding:2rem;">
                    <div style="font-size:3rem; opacity:0.2; color:var(--primary); margin-bottom:1rem;"><i class="fas fa-clock"></i></div>
                    <h3 style="color:var(--text);">No Certificates to Track</h3>
                    <p style="color:rgba(32,0,130,0.5);">Certificate expiry information will appear here.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="expiry-table" id="expiry-table">
                        <thead>
                            <tr>
                                <th>Learner</th>
                                <th>Course</th>
                                <th>Issued</th>
                                <th>Valid Until</th>
                                <th>Days Left</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiryData as $cert):
                                $name = htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']);
                                $validUntilRaw = $cert['valid_until'] ? date('Y-m-d', strtotime($cert['valid_until'])) : '';
                            ?>
                                <tr class="expiry-row" data-expiry="<?= $cert['expiry_status'] ?>" data-search="<?= strtolower($name . ' ' . htmlspecialchars($cert['course_title'])) ?>">
                                    <td>
                                        <div style="font-weight:600;"><?= $name ?></div>
                                        <div style="font-size:0.72rem; color:rgba(32,0,130,0.4);">ID: <?= htmlspecialchars($cert['email']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($cert['course_title']) ?></td>
                                    <td><?= $cert['issued_at'] ? date('M j, Y', strtotime($cert['issued_at'])) : 'N/A' ?></td>
                                    <td><?= $cert['valid_until'] ? date('M j, Y', strtotime($cert['valid_until'])) : 'No expiry' ?></td>
                                    <td>
                                        <?php if ($cert['days_left'] !== null && $cert['status'] === 'active'): ?>
                                            <span style="font-weight:700; color: <?= $cert['days_left'] <= 7 ? '#dc3545' : ($cert['days_left'] <= 30 ? '#d97706' : '#10b981') ?>;">
                                                <?= $cert['days_left'] > 0 ? $cert['days_left'] . 'd' : 'Expired' ?>
                                            </span>
                                        <?php else: ?>
                                            --
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $labels = ['valid' => 'Valid', 'warning' => 'Warning', 'critical' => 'Critical', 'expired' => 'Expired', 'archived' => 'Revoked'];
                                        $cls = $cert['expiry_status'] === 'archived' ? 'expired' : $cert['expiry_status'];
                                        ?>
                                        <span class="expiry-badge <?= $cls ?>"><?= $labels[$cert['expiry_status']] ?? 'Unknown' ?></span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:0.3rem; flex-wrap:nowrap;">
                                            <a href="?page=public/verify-certificate&code=<?= htmlspecialchars($cert['verification_code']) ?>" target="_blank" style="padding:0.3rem 0.6rem; background:rgba(32,0,130,0.08); color:var(--primary); border-radius:6px; font-size:0.72rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:0.2rem; white-space:nowrap;"><i class="fas fa-external-link-alt"></i> View</a>
                                            <?php if ($cert['status'] === 'active'): ?>
                                            <button type="button" class="expiry-extend-btn" data-id="<?= $cert['id'] ?>" data-course="<?= htmlspecialchars($cert['course_title'], ENT_QUOTES) ?>" data-current="<?= $validUntilRaw ?>" onclick="openExtendModal(this)" style="padding:0.3rem 0.6rem; background:rgba(16,185,129,0.08); color:#16a34a; border:none; border-radius:6px; font-size:0.72rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:0.2rem; white-space:nowrap;"><i class="fas fa-calendar-plus"></i> Extend</button>
                                            <button type="button" class="expiry-revoke-btn" data-id="<?= $cert['id'] ?>" data-course="<?= htmlspecialchars($cert['course_title'], ENT_QUOTES) ?>" onclick="confirmRevoke(this)" style="padding:0.3rem 0.6rem; background:rgba(220,53,69,0.08); color:#dc3545; border:none; border-radius:6px; font-size:0.72rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:0.2rem; white-space:nowrap;"><i class="fas fa-ban"></i> Revoke</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-row" id="expiry-pagination">
                    <button type="button" class="page-btn" data-action="prev" disabled>Prev</button>
                    <span class="page-indicator">Page 1 of 1</span>
                    <button type="button" class="page-btn" data-action="next">Next</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 3: Users (Ranked by cert count) -->
        <div class="tab-content" data-tab="tab-users">
            <?php if (!empty($userCerts)): ?>
            <div style="display:flex; align-items:center; justify-content:flex-end; margin-bottom:1rem; gap:0.5rem;">
                <label style="font-size:0.78rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.06em;">Sort by:</label>
                <select id="users-sort" style="padding:0.4rem 0.8rem; border:1px solid rgba(32,0,130,0.15); border-radius:8px; font-size:0.82rem; color:var(--text); background:var(--surface); cursor:pointer;">
                    <option value="most-certs">Most Certificates</option>
                    <option value="newly-acquired">Newly Acquired</option>
                </select>
            </div>
            <?php endif; ?>
            <?php if (empty($userCerts)): ?>
                <div class="mode-card" style="text-align:center; padding:2rem;">
                    <div style="font-size:3rem; opacity:0.2; color:var(--primary); margin-bottom:1rem;"><i class="fas fa-users"></i></div>
                    <h3 style="color:var(--text);">No Certificate Holders</h3>
                    <p style="color:rgba(32,0,130,0.5);">Users who earn certificates will appear here.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                        <thead>
                            <tr style="border-bottom:2px solid rgba(32,0,130,0.12); text-align:left;">
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Rank</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">User</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center;">Total Certs</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center;">Active</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Last Certificate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userCerts as $idx => $uc):
                                $name = htmlspecialchars($uc['first_name'] . ' ' . $uc['last_name']);
                                $initials = strtoupper(substr($uc['first_name'], 0, 1) . substr($uc['last_name'], 0, 1));
                                $rank = $idx + 1;
                                $rankBadge = $rank === 1 ? 'linear-gradient(135deg, #FFD700, #FFA500)' : ($rank === 2 ? 'linear-gradient(135deg, #C0C0C0, #A0A0A0)' : ($rank === 3 ? 'linear-gradient(135deg, #CD7F32, #A0522D)' : 'rgba(32,0,130,0.08)'));
                                $rankTextColor = $rank <= 3 ? '#fff' : 'rgba(32,0,130,0.5)';
                                $rankIcon = $rank === 1 ? 'fas fa-trophy' : ($rank === 2 ? 'fas fa-medal' : ($rank === 3 ? 'fas fa-award' : ''));
                                $rowBg = $rank === 1 ? 'rgba(255,215,0,0.04)' : ($rank === 2 ? 'rgba(192,192,192,0.03)' : ($rank === 3 ? 'rgba(205,125,50,0.02)' : ''));
                            ?>
                                <tr class="user-cert-row" data-search="<?= strtolower($name . ' ' . $uc['email']) ?>" data-cert-count="<?= $uc['cert_count'] ?>" data-last-cert="<?= $uc['last_cert_date'] ?>" style="border-bottom:1px solid rgba(32,0,130,0.06); transition:background 0.15s;<?= $rowBg ? ' background:' . $rowBg . ';' : '' ?>" onmouseover="this.style.background='rgba(32,0,130,0.03)'" onmouseout="this.style.background='<?= $rowBg ?>'">
                                    <td style="padding:0.85rem 1rem;">
                                        <div style="width:36px; height:36px; border-radius:50%; background:<?= $rankBadge ?>; color:<?= $rankTextColor ?>; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.8rem;">
                                            <?php if ($rankIcon): ?><i class="<?= $rankIcon ?>" style="font-size:0.85rem;"></i><?php else: ?><?= $rank ?><?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="padding:0.85rem 1rem;">
                                        <div style="display:flex; align-items:center; gap:0.65rem;">
                                            <div style="width:36px; height:36px; min-width:36px; border-radius:50%; background:linear-gradient(135deg, rgba(16,185,129,0.9), rgba(52,211,153,0.75)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.75rem;"><?= $initials ?></div>
                                            <div>
                                                <div style="font-weight:700; color:var(--text);"><?= $name ?></div>
                                                <div style="font-size:0.75rem; color:rgba(32,0,130,0.45);"><?= htmlspecialchars($uc['email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding:0.85rem 1rem; text-align:center;">
                                        <span style="display:inline-block; padding:0.3rem 0.7rem; border-radius:999px; font-weight:800; font-size:0.9rem; background:var(--primary); color:var(--surface);"><?= $uc['cert_count'] ?></span>
                                    </td>
                                    <td style="padding:0.85rem 1rem; text-align:center; font-weight:700; color:#10b981;"><?= $uc['active_count'] ?></td>
                                    <td style="padding:0.85rem 1rem; font-size:0.85rem; color:rgba(32,0,130,0.5);"><?= certTimeAgo($uc['last_cert_date']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 4: Templates -->
        <div class="tab-content" data-tab="tab-templates">
            <div style="display:flex; align-items:center; justify-content:flex-end; margin-bottom:1rem;">
                <button id="btn-add-template" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.55rem 1.1rem; border-radius:8px; border:none; background:var(--primary); color:var(--surface); font-weight:700; font-size:0.82rem; cursor:pointer; transition:opacity 0.15s;" onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'"><i class="fas fa-plus"></i> Add Template</button>
            </div>
            <?php if (empty($templates)): ?>
                <div class="mode-card" style="text-align:center; padding:2rem;">
                    <div style="font-size:3rem; opacity:0.2; color:var(--primary); margin-bottom:1rem;"><i class="fas fa-file-alt"></i></div>
                    <h3 style="color:var(--text);">No Templates</h3>
                    <p style="color:rgba(32,0,130,0.5);">Create certificate templates to standardize course completion awards.</p>
                </div>
            <?php else: ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1.25rem;">
                    <?php foreach ($templates as $t):
                        $isLinked = !empty($t['course_title']);
                    ?>
                        <div class="template-card" data-search="<?= strtolower($t['title'] . ' ' . ($t['course_title'] ?? '')) ?>" style="border:1px solid rgba(32,0,130,0.12); border-radius:14px; overflow:hidden; transition:transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
                            <div style="background:linear-gradient(135deg, rgba(32,0,130,0.08), rgba(81,70,183,0.05)); padding:1.25rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
                                    <div style="font-weight:700; color:var(--text); font-size:1.05rem;"><?= htmlspecialchars($t['title']) ?></div>
                                    <span style="padding:0.2rem 0.5rem; border-radius:999px; font-size:0.65rem; font-weight:700; background:rgba(16,185,129,0.1); color:#10b981; white-space:nowrap;">Active</span>
                                </div>
                                <div style="font-size:0.8rem; color:rgba(32,0,130,0.5); margin-top:0.25rem;"><?= $isLinked ? htmlspecialchars($t['course_title']) : 'General Template' ?></div>
                            </div>
                            <div style="padding:1rem 1.25rem;">
                                <?php if ($t['description']): ?>
                                    <p style="font-size:0.85rem; color:rgba(32,0,130,0.6); line-height:1.6; margin-bottom:0.75rem;"><?= htmlspecialchars(mb_substr($t['description'], 0, 150)) ?><?= mb_strlen($t['description']) > 150 ? '...' : '' ?></p>
                                <?php endif; ?>
                                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:rgba(32,0,130,0.4);">
                                    <span>Created <?= date('M j, Y', strtotime($t['created_at'])) ?></span>
                                    <div style="display:flex; gap:0.4rem;">
                                        <button class="btn-edit-template" data-id="<?= $t['id'] ?>" data-title="<?= htmlspecialchars($t['title'], ENT_QUOTES) ?>" data-desc="<?= htmlspecialchars($t['description'] ?? '', ENT_QUOTES) ?>" data-course="<?= $t['course_id'] ?? '' ?>" style="padding:0.35rem 0.7rem; border-radius:6px; border:1px solid rgba(32,0,130,0.15); background:transparent; color:var(--primary, #320082); font-size:0.75rem; font-weight:700; cursor:pointer; transition:background 0.15s;" onmouseover="this.style.background='rgba(32,0,130,0.06)'" onmouseout="this.style.background='transparent'"><i class="fas fa-pen" style="margin-right:0.2rem;"></i>Edit</button>
                                        <button class="btn-delete-template" data-id="<?= $t['id'] ?>" data-title="<?= htmlspecialchars($t['title'], ENT_QUOTES) ?>" style="padding:0.35rem 0.7rem; border-radius:6px; border:1px solid rgba(239,68,68,0.2); background:transparent; color:#ef4444; font-size:0.75rem; font-weight:700; cursor:pointer; transition:background 0.15s;" onmouseover="this.style.background='rgba(239,68,68,0.06)'" onmouseout="this.style.background='transparent'"><i class="fas fa-trash" style="margin-right:0.2rem;"></i>Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Confirm Modal -->
<div id="confirm-modal-overlay" class="modal-overlay" style="display:none; z-index:10000; background:rgba(0,0,0,0.45);backdrop-filter:blur(3px);">
    <div style="background:var(--surface, #fff); border-radius:16px; width:90%; max-width:400px; box-shadow:0 20px 60px rgba(0,0,0,0.2); padding:1.5rem; text-align:center;">
        <div style="font-size:2rem; color:#ef4444; margin-bottom:0.75rem;"><i class="fas fa-exclamation-triangle"></i></div>
        <p id="confirm-modal-message" style="font-size:0.95rem; color:var(--text, #222); margin-bottom:1.25rem; line-height:1.5;"></p>
        <div style="display:flex; gap:0.75rem; justify-content:center;">
            <button id="confirm-modal-cancel" style="padding:0.55rem 1.2rem; border:1.5px solid rgba(32,0,130,0.15); border-radius:8px; background:transparent; color:var(--text, #222); font-weight:700; font-size:0.85rem; cursor:pointer;">Cancel</button>
            <button id="confirm-modal-ok" style="padding:0.55rem 1.2rem; border:none; border-radius:8px; background:#ef4444; color:#fff; font-weight:700; font-size:0.85rem; cursor:pointer;">Delete</button>
        </div>
    </div>
</div>

<!-- Add Template Modal -->
<div id="template-modal-overlay" class="modal-overlay" style="display:none; z-index:9999; background:rgba(0,0,0,0.45);backdrop-filter:blur(3px);">
    <div style="background:var(--surface, #fff); border-radius:16px; width:90%; max-width:520px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32,0,130,0.08);">
            <h3 id="template-modal-title" style="margin:0; font-size:1.1rem; font-weight:800; color:var(--text, #222);">New Certificate Template</h3>
            <button id="close-template-modal" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:rgba(32,0,130,0.4); padding:0.25rem;"><i class="fas fa-times"></i></button>
        </div>
        <form id="template-form" style="padding:1.5rem;">
            <input type="hidden" name="template_id" id="template-form-id" value="" />
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--primary, #320082); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.4rem;">Template Title *</label>
                <input type="text" name="title" required placeholder="e.g. Standard Course Completion" style="width:100%; padding:0.6rem 0.8rem; border:1.5px solid rgba(32,0,130,0.12); border-radius:8px; font-size:0.9rem; background:var(--surface, #fff); color:var(--text, #222); box-sizing:border-box;" />
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--primary, #320082); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.4rem;">Description</label>
                <textarea name="description" rows="3" placeholder="Brief description of this template..." style="width:100%; padding:0.6rem 0.8rem; border:1.5px solid rgba(32,0,130,0.12); border-radius:8px; font-size:0.9rem; resize:vertical; background:var(--surface, #fff); color:var(--text, #222); box-sizing:border-box;"></textarea>
            </div>
            <div style="margin-bottom:1.25rem;">
                <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--primary, #320082); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.4rem;">Link to Course (optional)</label>
                <select name="course_id" style="width:100%; padding:0.6rem 0.8rem; border:1.5px solid rgba(32,0,130,0.12); border-radius:8px; font-size:0.9rem; background:var(--surface, #fff); color:var(--text, #222); box-sizing:border-box;">
                    <option value="">-- General Template --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
                <button type="button" id="cancel-template-modal" style="padding:0.55rem 1.2rem; border:1.5px solid rgba(32,0,130,0.15); border-radius:8px; background:transparent; color:var(--text, #222); font-weight:700; font-size:0.85rem; cursor:pointer;">Cancel</button>
                <button type="submit" id="template-form-submit" style="padding:0.55rem 1.2rem; border:none; border-radius:8px; background:var(--primary, #320082); color:var(--surface, #fff); font-weight:700; font-size:0.85rem; cursor:pointer;"><i class="fas fa-save" style="margin-right:0.3rem;"></i> Create Template</button>
            </div>
        </form>
    </div>
</div>

<!-- Extend Modal -->
<div id="extend-modal-overlay" class="modal-overlay" style="display:none; z-index:10001; background:rgba(0,0,0,0.45); backdrop-filter:blur(3px);">
    <div style="background:var(--surface, #fff); border-radius:16px; width:90%; max-width:420px; box-shadow:0 20px 60px rgba(0,0,0,0.2); padding:1.5rem;">
        <h3 style="margin:0 0 0.5rem; font-size:1.1rem; font-weight:800; color:var(--text);">Extend Certificate</h3>
        <p style="margin:0 0 1rem; font-size:0.85rem; color:rgba(32,0,130,0.5);">Set a new expiry date for <strong id="extend-course-name"></strong>.</p>
        <div style="margin-bottom:1rem;">
            <label style="display:block; font-size:0.78rem; font-weight:600; color:var(--text); margin-bottom:0.3rem;">New Expiry Date</label>
            <input type="date" id="extend-date-input" style="width:100%; padding:0.6rem 0.8rem; border:1.5px solid rgba(32,0,130,0.15); border-radius:8px; font-size:0.88rem; background:var(--surface, #fff); color:var(--text); box-sizing:border-box;">
        </div>
        <div id="extend-error" style="display:none; padding:0.5rem 0.75rem; background:rgba(220,53,69,0.08); color:#dc3545; border-radius:8px; font-size:0.82rem; margin-bottom:1rem;"></div>
        <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
            <button type="button" onclick="closeExtendModal()" style="padding:0.55rem 1.1rem; background:rgba(32,0,130,0.08); color:var(--primary); border:none; border-radius:8px; font-size:0.82rem; font-weight:600; cursor:pointer;">Cancel</button>
            <button type="button" id="extend-confirm-btn" onclick="submitExtend()" style="padding:0.55rem 1.1rem; background:#16a34a; color:#fff; border:none; border-radius:8px; font-size:0.82rem; font-weight:600; cursor:pointer;">Extend</button>
        </div>
    </div>
</div>

<!-- Revoke Confirm Modal -->
<div id="revoke-modal-overlay" class="modal-overlay" style="display:none; z-index:10001; background:rgba(0,0,0,0.45); backdrop-filter:blur(3px);">
    <div style="background:var(--surface, #fff); border-radius:16px; width:90%; max-width:400px; box-shadow:0 20px 60px rgba(0,0,0,0.2); padding:1.5rem; text-align:center;">
        <div style="font-size:2.5rem; color:#dc3545; margin-bottom:0.75rem;"><i class="fas fa-exclamation-triangle"></i></div>
        <h3 style="margin:0 0 0.5rem; font-size:1.1rem; color:var(--text);">Revoke Certificate?</h3>
        <p style="margin:0 0 1.25rem; font-size:0.85rem; color:rgba(32,0,130,0.5);">This will revoke the certificate for <strong id="revoke-course-name"></strong>. The learner will lose access to this credential.</p>
        <div id="revoke-error" style="display:none; padding:0.5rem 0.75rem; background:rgba(220,53,69,0.08); color:#dc3545; border-radius:8px; font-size:0.82rem; margin-bottom:1rem;"></div>
        <div style="display:flex; gap:0.5rem; justify-content:center;">
            <button type="button" onclick="closeRevokeModal()" style="padding:0.55rem 1.1rem; background:rgba(32,0,130,0.08); color:var(--primary); border:none; border-radius:8px; font-size:0.82rem; font-weight:600; cursor:pointer;">Cancel</button>
            <button type="button" id="revoke-confirm-btn" onclick="submitRevoke()" style="padding:0.55rem 1.1rem; background:#dc3545; color:#fff; border:none; border-radius:8px; font-size:0.82rem; font-weight:600; cursor:pointer;">Revoke</button>
        </div>
    </div>
</div>

<!-- Certificate Detail Modal -->
<div id="cert-detail-modal" class="modal-overlay" style="display:none; z-index:10000; background:rgba(0,0,0,0.45); backdrop-filter:blur(3px);">
    <div style="background:var(--surface, #fff); border-radius:18px; width:90%; max-width:520px; max-height:88vh; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.25); display:flex; flex-direction:column;">
        <div id="cert-detail-header" style="background:linear-gradient(135deg, rgba(16,185,129,0.1), rgba(52,211,153,0.06)); padding:1.75rem 1.5rem; text-align:center; border-bottom:1px solid rgba(16,185,129,0.12); position:relative;">
            <button onclick="document.getElementById('cert-detail-modal').style.display='none'" style="position:absolute; top:0.75rem; right:0.75rem; background:none; border:none; font-size:1.2rem; cursor:pointer; color:rgba(32,0,130,0.4); padding:0.25rem;"><i class="fas fa-times"></i></button>
            <div style="font-size:2.5rem; color:#10b981; margin-bottom:0.5rem;"><i class="fas fa-award"></i></div>
            <h2 id="cert-detail-course" style="margin:0 0 0.25rem; font-size:1.2rem; font-weight:800; color:var(--text);"></h2>
            <div id="cert-detail-status" style="display:flex; flex-direction:column; align-items:center; gap:0.3rem;"></div>
        </div>
        <div style="padding:1.5rem; overflow-y:auto; flex:1;">
            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1.25rem; padding-bottom:1.25rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                <div id="cert-detail-avatar" style="width:48px; height:48px; border-radius:50%; background:linear-gradient(135deg, rgba(16,185,129,0.9), rgba(52,211,153,0.75)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; flex-shrink:0;"></div>
                <div>
                    <div id="cert-detail-name" style="font-weight:700; color:var(--text); font-size:1rem;"></div>
                    <div id="cert-detail-email" style="font-size:0.8rem; color:rgba(32,0,130,0.45);"></div>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.25rem;">
                <div>
                    <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--primary); font-weight:700;">Issued</div>
                    <div id="cert-detail-issued" style="color:var(--text); margin-top:0.2rem; font-size:0.92rem; font-weight:600;"></div>
                </div>
                <div>
                    <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--primary); font-weight:700;">Valid Until</div>
                    <div id="cert-detail-valid" style="color:var(--text); margin-top:0.2rem; font-size:0.92rem; font-weight:600;"></div>
                </div>
            </div>
            <div style="margin-bottom:1.25rem;">
                <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--primary); font-weight:700; margin-bottom:0.4rem;">Verification Code</div>
                <div id="cert-detail-code" style="font-family:monospace; font-size:0.82rem; color:rgba(32,0,130,0.6); background:rgba(32,0,130,0.04); padding:0.6rem 0.85rem; border-radius:8px; word-break:break-all;"></div>
            </div>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <a id="cert-detail-view" href="#" target="_blank" style="flex:1; text-align:center; padding:0.6rem; border-radius:8px; font-size:0.85rem; font-weight:700; background:var(--primary); color:var(--surface); text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:0.4rem;"><i class="fas fa-external-link-alt"></i> Verify</a>
                <button id="cert-detail-extend" style="flex:1; padding:0.6rem; border-radius:8px; font-size:0.85rem; font-weight:700; background:rgba(16,185,129,0.1); color:#10b981; border:none; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:0.4rem;"><i class="fas fa-clock"></i> Extend</button>
                <button id="cert-detail-revoke" style="flex:1; padding:0.6rem; border-radius:8px; font-size:0.85rem; font-weight:700; background:rgba(239,68,68,0.1); color:#ef4444; border:none; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:0.4rem;"><i class="fas fa-ban"></i> Revoke</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var searchInput = document.getElementById('cert-search');
    var statusFilter = document.getElementById('cert-status-filter');

    function filterAll() {
        var query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        var status = statusFilter ? statusFilter.value : 'all';

        // Filter certificate cards
        document.querySelectorAll('.cert-card-item').forEach(function(card) {
            var searchText = (card.getAttribute('data-search') || '').toLowerCase();
            var rowStatus = card.getAttribute('data-status') || '';
            var matchSearch = query === '' || searchText.indexOf(query) > -1;
            var matchStatus = status === 'all' || rowStatus === status;
            card.style.display = (matchSearch && matchStatus) ? '' : 'none';
        });

        // Filter user rows
        document.querySelectorAll('.user-cert-row').forEach(function(row) {
            var searchText = (row.getAttribute('data-search') || '').toLowerCase();
            var matchSearch = query === '' || searchText.indexOf(query) > -1;
            row.style.display = matchSearch ? '' : 'none';
        });

        // Filter template cards
        document.querySelectorAll('.template-card').forEach(function(card) {
            var searchText = (card.getAttribute('data-search') || '').toLowerCase();
            var matchSearch = query === '' || searchText.indexOf(query) > -1;
            card.style.display = matchSearch ? '' : 'none';
        });

        // Filter expiry table rows
        var expiryFilter = document.getElementById('expiry-filter');
        var expiryVal = expiryFilter ? expiryFilter.value : 'all';
        document.querySelectorAll('.expiry-row').forEach(function(row) {
            var searchText = (row.getAttribute('data-search') || '').toLowerCase();
            var expiryStatus = row.getAttribute('data-expiry') || '';
            var matchSearch = query === '' || searchText.indexOf(query) > -1;
            var matchExpiry = expiryVal === 'all' || expiryStatus === expiryVal;
            row.style.display = (matchSearch && matchExpiry) ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterAll);
    if (statusFilter) statusFilter.addEventListener('change', filterAll);

    var expiryFilter = document.getElementById('expiry-filter');
    if (expiryFilter) expiryFilter.addEventListener('change', filterAll);

    // Expiry table pagination
    var expiryPageSize = 12;
    var expiryPage = 1;
    function paginateExpiry() {
        var rows = Array.from(document.querySelectorAll('.expiry-row'));
        var ef = document.getElementById('expiry-filter');
        var efilter = ef ? ef.value : 'all';
        var q = (searchInput ? searchInput.value : '').toLowerCase().trim();
        var vis = rows.filter(function(r) {
            var txt = (r.getAttribute('data-search') || '').toLowerCase();
            var es = r.getAttribute('data-expiry') || '';
            return (q === '' || txt.indexOf(q) > -1) && (efilter === 'all' || es === efilter);
        });
        var tot = Math.max(1, Math.ceil(vis.length / expiryPageSize));
        expiryPage = Math.min(expiryPage, tot);
        var st = (expiryPage - 1) * expiryPageSize;
        vis.forEach(function(r, i) { r.style.display = (i >= st && i < st + expiryPageSize) ? '' : 'none'; });
        var pg = document.getElementById('expiry-pagination');
        if (pg) {
            pg.querySelector('.page-indicator').textContent = 'Page ' + expiryPage + ' of ' + tot;
            pg.querySelector('[data-action="prev"]').disabled = expiryPage <= 1;
            pg.querySelector('[data-action="next"]').disabled = expiryPage >= tot;
            pg.style.display = tot <= 1 ? 'none' : '';
        }
    }
    var expiryPg = document.getElementById('expiry-pagination');
    if (expiryPg) {
        expiryPg.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-action]');
            if (!btn || btn.disabled) return;
            if (btn.dataset.action === 'prev' && expiryPage > 1) expiryPage--;
            if (btn.dataset.action === 'next') expiryPage++;
            paginateExpiry();
        });
    }
    if (expiryFilter) expiryFilter.addEventListener('change', function() { expiryPage = 1; paginateExpiry(); });
    // Users sort
    var usersSort = document.getElementById('users-sort');
    if (usersSort) {
        usersSort.addEventListener('change', function() {
            var tbody = document.querySelector('[data-tab="tab-users"] tbody');
            if (!tbody) return;
            var rows = Array.from(tbody.querySelectorAll('.user-cert-row'));
            var sortBy = usersSort.value;

            rows.sort(function(a, b) {
                if (sortBy === 'most-certs') {
                    var ca = parseInt(a.getAttribute('data-cert-count') || '0', 10);
                    var cb = parseInt(b.getAttribute('data-cert-count') || '0', 10);
                    if (cb !== ca) return cb - ca;
                    var da = a.getAttribute('data-last-cert') || '';
                    var db = b.getAttribute('data-last-cert') || '';
                    return db.localeCompare(da);
                } else {
                    var da = a.getAttribute('data-last-cert') || '';
                    var db = b.getAttribute('data-last-cert') || '';
                    if (db !== da) return db.localeCompare(da);
                    var ca = parseInt(a.getAttribute('data-cert-count') || '0', 10);
                    var cb = parseInt(b.getAttribute('data-cert-count') || '0', 10);
                    return cb - ca;
                }
            });

            rows.forEach(function(row, i) {
                tbody.appendChild(row);
                var rankDiv = row.querySelector('td:first-child > div');
                if (rankDiv) {
                    var rank = i + 1;
                    if (rank === 1) { rankDiv.style.background = 'linear-gradient(135deg, #FFD700, #FFA500)'; rankDiv.style.color = '#fff'; rankDiv.innerHTML = '<i class="fas fa-trophy" style="font-size:0.85rem;"></i>'; }
                    else if (rank === 2) { rankDiv.style.background = 'linear-gradient(135deg, #C0C0C0, #A0A0A0)'; rankDiv.style.color = '#fff'; rankDiv.innerHTML = '<i class="fas fa-medal" style="font-size:0.85rem;"></i>'; }
                    else if (rank === 3) { rankDiv.style.background = 'linear-gradient(135deg, #CD7F32, #A0522D)'; rankDiv.style.color = '#fff'; rankDiv.innerHTML = '<i class="fas fa-award" style="font-size:0.85rem;"></i>'; }
                    else { rankDiv.style.background = 'rgba(32,0,130,0.08)'; rankDiv.style.color = 'rgba(32,0,130,0.5)'; rankDiv.innerHTML = rank; }
                }
                var rowBg = rank === 1 ? 'rgba(255,215,0,0.04)' : (rank === 2 ? 'rgba(192,192,192,0.03)' : (rank === 3 ? 'rgba(205,125,50,0.02)' : ''));
                row.style.background = rowBg;
                row.setAttribute('onmouseout', "this.style.background='" + rowBg + "'");
            });
        });
    }

    // Cert card pagination\r\n    var certPageSize = 9;\r\n    var certPage = 1;\r\n    function paginateCerts() {\r\n        var cards = Array.from(document.querySelectorAll(".cert-card-item"));\r\n        var query = (searchInput ? searchInput.value : "").toLowerCase().trim();\r\n        var status = statusFilter ? statusFilter.value : "all";\r\n        var visible = cards.filter(function(c) {\r\n            var txt = (c.getAttribute("data-search") || "").toLowerCase();\r\n            var st = c.getAttribute("data-status") || "";\r\n            return (query === "" || txt.indexOf(query) > -1) && (status === "all" || st === status);\r\n        });\r\n        var totalPages = Math.max(1, Math.ceil(visible.length / certPageSize));\r\n        certPage = Math.min(certPage, totalPages);\r\n        var start = (certPage - 1) * certPageSize;\r\n        cards.forEach(function(c) { c.style.display = "none"; });\r\n        visible.slice(start, start + certPageSize).forEach(function(c) { c.style.display = ""; });\r\n        var pg = document.getElementById("cert-pagination");\r\n        if (pg) {\r\n            pg.querySelector(".page-indicator").textContent = "Page " + certPage + " of " + totalPages;\r\n            pg.querySelector("[data-action=prev]").disabled = certPage <= 1;\r\n            pg.querySelector("[data-action=next]").disabled = certPage >= totalPages;\r\n            pg.style.display = totalPages <= 1 ? "none" : "";\r\n        }\r\n    }\r\n    var certPg = document.getElementById("cert-pagination");\r\n    if (certPg) {\r\n        certPg.addEventListener("click", function(e) {\r\n            var btn = e.target.closest("[data-action]");\r\n            if (!btn || btn.disabled) return;\r\n            if (btn.dataset.action === "prev" && certPage > 1) certPage--;\r\n            if (btn.dataset.action === "next") certPage++;\r\n            paginateCerts();\r\n        });\r\n    }\r\n    // Override filterAll to also paginate certs\r\n    var origFilterAll = filterAll;\r\n    filterAll = function() { origFilterAll(); paginateCerts(); };\r\n\r\n    // --- Template Modal ---
    function openTemplateModal() { document.getElementById('template-modal-overlay').style.display = 'flex'; }
    function closeTemplateModal() {
        document.getElementById('template-modal-overlay').style.display = 'none';
        document.getElementById('template-form').reset();
        document.getElementById('template-form-id').value = '';
        document.getElementById('template-modal-title').textContent = 'New Certificate Template';
        document.getElementById('template-form-submit').innerHTML = '<i class="fas fa-save" style="margin-right:0.3rem;"></i> Create Template';
    }
    function openEditModal(id, title, desc, courseId) {
        document.getElementById('template-form-id').value = id;
        document.getElementById('template-modal-title').textContent = 'Edit Certificate Template';
        document.getElementById('template-form-submit').innerHTML = '<i class="fas fa-save" style="margin-right:0.3rem;"></i> Save Changes';
        var form = document.getElementById('template-form');
        form.querySelector('input[name=title]').value = title;
        form.querySelector('textarea[name=description]').value = desc;
        form.querySelector('select[name=course_id]').value = courseId;
        document.getElementById('template-modal-overlay').style.display = 'flex';
    }
    document.getElementById('btn-add-template').addEventListener('click', openTemplateModal);
    document.getElementById('close-template-modal').addEventListener('click', closeTemplateModal);
    document.getElementById('cancel-template-modal').addEventListener('click', closeTemplateModal);
    document.getElementById('template-modal-overlay').addEventListener('click', function(e) { if (e.target.id === 'template-modal-overlay') closeTemplateModal(); });

    // --- Confirm Modal ---
    var confirmCallback = null;
    function openConfirmModal(msg, onConfirm) {
        document.getElementById('confirm-modal-message').textContent = msg;
        document.getElementById('confirm-modal-overlay').style.display = 'flex';
        confirmCallback = onConfirm;
    }
    document.getElementById('confirm-modal-ok').addEventListener('click', function() {
        document.getElementById('confirm-modal-overlay').style.display = 'none';
        if (confirmCallback) confirmCallback();
        confirmCallback = null;
    });
    document.getElementById('confirm-modal-cancel').addEventListener('click', function() {
        document.getElementById('confirm-modal-overlay').style.display = 'none';
        confirmCallback = null;
    });

    // Edit buttons
    document.querySelectorAll('.btn-edit-template').forEach(function(btn) {
        btn.addEventListener('click', function() {
            openEditModal(btn.getAttribute('data-id'), btn.getAttribute('data-title'), btn.getAttribute('data-desc'), btn.getAttribute('data-course'));
        });
    });

    // Delete buttons
    document.querySelectorAll('.btn-delete-template').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.getAttribute('data-id');
            var name = btn.getAttribute('data-title');
            openConfirmModal('Delete template "' + name + '"? This cannot be undone.', function() {
                var fd = new FormData();
                fd.append('template_id', id);
                fetch('pages/instructor/ajax/delete-certificate-template.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        if (window.showToast) window.showToast('Template deleted', 'success');
                        setTimeout(function(){ location.reload(); }, 800);
                    } else {
                        if (window.showToast) window.showToast(data.error || 'Failed to delete template', 'error');
                    }
                }).catch(function() { if (window.showToast) window.showToast('Network error', 'error'); });
            });
        });
    });

    document.getElementById('template-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = document.getElementById('template-form');
        var fd = new FormData(form);
        var isEdit = fd.get('template_id') !== '';
        var url = isEdit ? 'pages/instructor/ajax/edit-certificate-template.php' : 'pages/instructor/ajax/add-certificate-template.php';
        var submitBtn = form.querySelector('button[type=submit]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:0.3rem;"></i> ' + (isEdit ? 'Saving...' : 'Creating...');
        fetch(url, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                closeTemplateModal();
                if (window.showToast) window.showToast(isEdit ? 'Template updated' : 'Template created', 'success');
                setTimeout(function(){ location.reload(); }, 800);
            } else {
                if (window.showToast) window.showToast(data.error || 'Failed to save template', 'error');
            }
        }).catch(function() { if (window.showToast) window.showToast('Network error', 'error'); })
        .finally(function() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save" style="margin-right:0.3rem;"></i> ' + (isEdit ? 'Save Changes' : 'Create Template');
        });
    });

    // --- Extend Modal ---
    var extendCertId = 0;
    window.openExtendModal = function(btn) {
        extendCertId = parseInt(btn.dataset.id);
        document.getElementById('extend-course-name').textContent = btn.dataset.course;
        var nextYear = new Date();
        nextYear.setFullYear(nextYear.getFullYear() + 1);
        document.getElementById('extend-date-input').value = btn.dataset.current || nextYear.toISOString().split('T')[0];
        document.getElementById('extend-error').style.display = 'none';
        document.getElementById('extend-modal-overlay').style.display = 'flex';
    };
    window.closeExtendModal = function() {
        document.getElementById('extend-modal-overlay').style.display = 'none';
        extendCertId = 0;
    };
    window.submitExtend = function() {
        var dateInput = document.getElementById('extend-date-input');
        var errorDiv = document.getElementById('extend-error');
        var btn = document.getElementById('extend-confirm-btn');
        var newDate = dateInput.value;
        if (!newDate) { errorDiv.textContent = 'Please select a date'; errorDiv.style.display = 'block'; return; }
        if (new Date(newDate) <= new Date()) { errorDiv.textContent = 'Date must be in the future'; errorDiv.style.display = 'block'; return; }
        btn.disabled = true;
        btn.textContent = 'Extending...';
        errorDiv.style.display = 'none';
        fetch('pages/admin/ajax/extend-certificate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ certificate_id: extendCertId, valid_until: newDate })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                closeExtendModal();
                if (window.showToast) window.showToast('Certificate extended to ' + data.new_date, 'success');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                errorDiv.textContent = data.error || 'Failed to extend';
                errorDiv.style.display = 'block';
            }
            btn.disabled = false;
            btn.textContent = 'Extend';
        }).catch(function() {
            errorDiv.textContent = 'Network error';
            errorDiv.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Extend';
        });
    };

    // --- Revoke Modal ---
    var revokeCertId = 0;
    window.confirmRevoke = function(btn) {
        revokeCertId = parseInt(btn.dataset.id);
        document.getElementById('revoke-course-name').textContent = btn.dataset.course;
        document.getElementById('revoke-error').style.display = 'none';
        document.getElementById('revoke-modal-overlay').style.display = 'flex';
    };
    window.closeRevokeModal = function() {
        document.getElementById('revoke-modal-overlay').style.display = 'none';
        revokeCertId = 0;
    };
    window.submitRevoke = function() {
        var errorDiv = document.getElementById('revoke-error');
        var btn = document.getElementById('revoke-confirm-btn');
        btn.disabled = true;
        btn.textContent = 'Revoking...';
        errorDiv.style.display = 'none';
        fetch('pages/admin/ajax/revoke-certificate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ certificate_id: revokeCertId })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                closeRevokeModal();
                if (window.showToast) window.showToast('Certificate revoked', 'success');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                errorDiv.textContent = data.error || 'Failed to revoke';
                errorDiv.style.display = 'block';
            }
            btn.disabled = false;
            btn.textContent = 'Revoke';
        }).catch(function() {
            errorDiv.textContent = 'Network error';
            errorDiv.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Revoke';
        });
    };

    // --- Certificate Detail Modal ---
    document.body.addEventListener('click', function(e) {
        var card = e.target.closest('.cert-card-item');
        if (!card) return;
        if (e.target.closest('a, button')) return;

        var name = card.getAttribute('data-search') || '';
        var status = card.getAttribute('data-status') || 'active';
        var expiryStatus = card.getAttribute('data-expiry') || 'valid';

        var headerEl = card.querySelector('[style*="background:linear-gradient"]');
        var courseTitle = '';
        if (headerEl) {
            var h4 = headerEl.querySelector('div[style*="font-weight:700"]');
            if (h4) courseTitle = h4.textContent.trim();
        }
        if (!courseTitle) {
            var allDivs = card.querySelectorAll('div[style*="font-weight:700"]');
            for (var i = 0; i < allDivs.length; i++) {
                if (allDivs[i].textContent.trim().length > 3 && !allDivs[i].closest('[style*="grid-template-columns"]')) {
                    courseTitle = allDivs[i].textContent.trim();
                    break;
                }
            }
        }

        var fullName = '';
        var email = '';
        var initials = '';
        var nameDivs = card.querySelectorAll('div[style*="font-weight:700"]');
        for (var j = 0; j < nameDivs.length; j++) {
            var t = nameDivs[j].textContent.trim();
            if (t.indexOf('@') === -1 && t !== courseTitle && t.length > 2 && t.length < 60) {
                fullName = t;
                break;
            }
        }
        var emailDiv = card.querySelector('div[style*="font-size:0.75rem"][style*="rgba(32,0,130,0.45)"]');
        if (emailDiv) email = emailDiv.textContent.trim();
        var initialsDiv = card.querySelector('div[style*="border-radius:50%"][style*="linear-gradient(135deg, rgba(16"]');
        if (initialsDiv) initials = initialsDiv.textContent.trim();

        var issued = '';
        var validUntil = '';
        var verifyCode = '';
        var verifyLink = '';
        var certId = 0;
        var gridLabels = card.querySelectorAll('div[style*="text-transform:uppercase"][style*="font-size:0.65rem"]');
        gridLabels.forEach(function(lbl) {
            var val = lbl.nextElementSibling;
            if (!val) return;
            var txt = val.textContent.trim();
            var label = lbl.textContent.trim().toLowerCase();
            if (label === 'issued') issued = txt;
            if (label === 'valid until') validUntil = txt;
        });
        var codeEl = card.querySelector('div[style*="font-family:monospace"]');
        if (codeEl) verifyCode = codeEl.textContent.trim();
        var viewLink = card.querySelector('a[href*="verify-certificate"]');
        if (viewLink) verifyLink = viewLink.getAttribute('href');
        var extendBtn = card.querySelector('.cert-extend-btn');
        var revokeBtn = card.querySelector('.cert-revoke-btn');
        if (extendBtn) certId = parseInt(extendBtn.dataset.id) || 0;

        var statusColors = {active:'#10b981',warning:'#d97706',critical:'#dc3545',expired:'#ef4444',archived:'#f59e0b',valid:'#10b981'};
        var statusLabels = {active:'Active',warning:'Expiring Soon',critical:'Critical',expired:'Expired',archived:'Revoked',valid:'Valid'};
        var sColor = statusColors[expiryStatus] || statusColors[status] || '#10b981';
        var sLabel = statusLabels[expiryStatus] || statusLabels[status] || 'Active';

        document.getElementById('cert-detail-course').textContent = courseTitle || 'Certificate';
        document.getElementById('cert-detail-status').innerHTML = '<span style="display:inline-block; padding:0.2rem 0.6rem; border-radius:999px; font-size:0.7rem; font-weight:700; background:' + sColor + '15; color:' + sColor + ';">' + sLabel + '</span>';
        document.getElementById('cert-detail-avatar').textContent = initials || '??';
        document.getElementById('cert-detail-name').textContent = fullName || 'Unknown';
        document.getElementById('cert-detail-email').textContent = email || '';
        document.getElementById('cert-detail-issued').textContent = issued || 'N/A';
        document.getElementById('cert-detail-valid').textContent = validUntil || 'No expiry';
        document.getElementById('cert-detail-code').textContent = verifyCode || '—';
        document.getElementById('cert-detail-view').href = verifyLink || '#';

        var extendBtnModal = document.getElementById('cert-detail-extend');
        var revokeBtnModal = document.getElementById('cert-detail-revoke');
        if (extendBtn && extendBtnModal) {
            extendBtnModal.style.display = '';
            extendBtnModal.onclick = function() { extendBtn.click(); document.getElementById('cert-detail-modal').style.display = 'none'; };
        } else if (extendBtnModal) {
            extendBtnModal.style.display = 'none';
        }
        if (revokeBtn && revokeBtnModal) {
            revokeBtnModal.style.display = '';
            revokeBtnModal.onclick = function() { revokeBtn.click(); document.getElementById('cert-detail-modal').style.display = 'none'; };
        } else if (revokeBtnModal) {
            revokeBtnModal.style.display = 'none';
        }

        document.getElementById('cert-detail-modal').style.display = 'flex';
    });
    document.getElementById('cert-detail-modal').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
})();
</script>
