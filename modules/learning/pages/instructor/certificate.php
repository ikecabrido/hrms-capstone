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
    DbError::capture($e, 'instructor/certificate');
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

$expiryStatusLabels = ['valid' => 'Valid', 'warning' => 'Warning', 'critical' => 'Critical', 'expired' => 'Expired', 'archived' => 'Revoked'];
?>
<div class="module-content cert-page">
    <div class="home-dashboard">
        <section class="home-hero" aria-labelledby="cert-title">
            <div class="home-hero-main">
                <h1 id="cert-title">Certificates</h1>
                <p>Issue, verify and monitor the certificates your learners have earned, and manage the templates behind them.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-award"></i> <?= $stats['total'] ?> issued</span>
                    <span class="home-hero-chip"><i class="fas fa-circle-check"></i> <?= $stats['active'] ?> active</span>
                    <span class="home-hero-chip"><i class="fas fa-hourglass-half"></i> <?= $stats['expiring_30'] ?> expiring &le;30d</span>
                    <span class="home-hero-chip"><i class="fas fa-triangle-exclamation"></i> <?= $stats['expiring_7'] ?> expiring &le;7d</span>
                    <span class="home-hero-chip"><i class="fas fa-ban"></i> <?= $stats['archived'] ?> revoked</span>
                </div>
            </div>
        </section>

        <div class="cert-toolbar">
            <label class="cert-search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="cert-search" placeholder="Search certificates, users, or templates..." aria-label="Search certificates" />
            </label>
            <label class="cert-select">
                <select id="cert-status-filter" aria-label="Filter by status">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="archived">Revoked</option>
                </select>
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </label>
            <span class="cert-count" id="cert-count" aria-live="polite"><?= count($issuedCertificates) ?> certificate<?= count($issuedCertificates) === 1 ? '' : 's' ?></span>
        </div>

        <div class="tab-container">
            <div class="tab-list" role="tablist">
                <button type="button" class="tab-item active" data-tab="tab-certificates" role="tab" aria-selected="true"><i class="fas fa-award" aria-hidden="true"></i> Certificates <span class="cert-tab-count"><?= count($issuedCertificates) ?></span></button>
                <button type="button" class="tab-item" data-tab="tab-expiry" role="tab" aria-selected="false"><i class="fas fa-hourglass-half" aria-hidden="true"></i> Expiry <span class="cert-tab-count"><?= $stats['expiring_30'] + $stats['expiring_7'] + $stats['expired'] ?></span></button>
                <button type="button" class="tab-item" data-tab="tab-users" role="tab" aria-selected="false"><i class="fas fa-users" aria-hidden="true"></i> Users <span class="cert-tab-count"><?= count($userCerts) ?></span></button>
                <button type="button" class="tab-item" data-tab="tab-templates" role="tab" aria-selected="false"><i class="fas fa-file-lines" aria-hidden="true"></i> Templates <span class="cert-tab-count"><?= count($templates) ?></span></button>
            </div>

            <!-- Tab 1: Certificates (card view) -->
            <div class="tab-content active" data-tab="tab-certificates" role="tabpanel">
                <?php if (empty($issuedCertificates)): ?>
                    <div class="cert-empty">
                        <i class="fas fa-award" aria-hidden="true"></i>
                        <strong>No certificates issued</strong>
                        <p>Certificates will appear here once learners complete their courses.</p>
                    </div>
                <?php else: ?>
                    <div class="cert-grid">
                        <?php foreach ($issuedCertificates as $cert):
                            $name = htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']);
                            $initials = strtoupper(substr($cert['first_name'], 0, 1) . substr($cert['last_name'], 0, 1));
                            $isExpired = $cert['valid_until'] && strtotime($cert['valid_until']) < time();
                            $daysLeft = $cert['days_left'] ?? null;
                            $statusLabel = $isExpired ? 'Expired' : ucfirst($cert['status']);
                            $statusTone = $isExpired ? 'is-danger' : ($cert['status'] === 'active' ? 'is-success' : 'is-muted');
                            $expiryFlag = '';
                            if ($cert['status'] === 'active' && $daysLeft !== null) {
                                if ($daysLeft <= 0) {
                                    $expiryFlag = '<span class="cert-flag is-danger"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> Expired</span>';
                                } elseif ($daysLeft <= 7) {
                                    $expiryFlag = '<span class="cert-flag is-danger"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> ' . $daysLeft . 'd left</span>';
                                } elseif ($daysLeft <= 30) {
                                    $expiryFlag = '<span class="cert-flag is-warn"><i class="fas fa-clock" aria-hidden="true"></i> ' . $daysLeft . 'd left</span>';
                                }
                            }
                            $issuedText = $cert['issued_at'] ? date('M j, Y', strtotime($cert['issued_at'])) : 'N/A';
                            $validText = $cert['valid_until'] ? date('M j, Y', strtotime($cert['valid_until'])) : 'No expiry';
                            $currentDate = $cert['valid_until'] ? date('Y-m-d', strtotime($cert['valid_until'])) : '';
                        ?>
                        <article class="cert-card-item" tabindex="0" role="button"
                            aria-label="View certificate details for <?= $name ?>"
                            data-search="<?= strtolower($name . ' ' . $cert['email'] . ' ' . $cert['course_title']) ?>"
                            data-status="<?= htmlspecialchars($cert['status']) ?>"
                            data-expiry="<?= htmlspecialchars($cert['expiry_status']) ?>"
                            data-course="<?= htmlspecialchars($cert['course_title'], ENT_QUOTES) ?>"
                            data-name="<?= htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name'], ENT_QUOTES) ?>"
                            data-email="<?= htmlspecialchars($cert['email'], ENT_QUOTES) ?>"
                            data-initials="<?= htmlspecialchars($initials, ENT_QUOTES) ?>"
                            data-issued="<?= htmlspecialchars($issuedText, ENT_QUOTES) ?>"
                            data-valid="<?= htmlspecialchars($validText, ENT_QUOTES) ?>"
                            data-code="<?= htmlspecialchars($cert['verification_code'], ENT_QUOTES) ?>"
                            data-verify="?page=public/verify-certificate&amp;back=instructor/certificate&amp;code=<?= htmlspecialchars(urlencode($cert['verification_code']), ENT_QUOTES) ?>">
                            <div class="cert-band">
                                <i class="fas fa-award cert-band-icon" aria-hidden="true"></i>
                                <div class="cert-band-title"><?= htmlspecialchars($cert['course_title']) ?></div>
                                <div class="cert-flags">
                                    <span class="cert-flag <?= $statusTone ?>"><?= htmlspecialchars($statusLabel) ?></span>
                                    <?= $expiryFlag ?>
                                </div>
                            </div>
                            <div class="cert-body">
                                <div class="cert-person">
                                    <span class="cert-avatar" aria-hidden="true"><?= htmlspecialchars($initials) ?></span>
                                    <div class="cert-person-text">
                                        <div class="cert-person-name"><?= $name ?></div>
                                        <div class="cert-person-mail"><?= htmlspecialchars($cert['email']) ?></div>
                                    </div>
                                </div>
                                <div class="cert-meta">
                                    <div>
                                        <span class="cert-meta-label">Issued</span>
                                        <span class="cert-meta-value"><?= $issuedText ?></span>
                                    </div>
                                    <div>
                                        <span class="cert-meta-label">Valid Until</span>
                                        <span class="cert-meta-value"><?= $validText ?></span>
                                    </div>
                                </div>
                                <div class="cert-code"><i class="fas fa-fingerprint" aria-hidden="true"></i><span><?= htmlspecialchars($cert['verification_code']) ?></span></div>
                                <div class="cert-actions">
                                    <a class="cert-btn is-primary" href="?page=public/verify-certificate&amp;back=instructor/certificate&amp;code=<?= htmlspecialchars($cert['verification_code']) ?>"><i class="fas fa-eye" aria-hidden="true"></i> View</a>
                                    <?php if ($cert['status'] === 'active'): ?>
                                    <button type="button" class="cert-btn is-icon is-success cert-extend-btn" data-id="<?= (int) $cert['id'] ?>" data-course="<?= htmlspecialchars($cert['course_title'], ENT_QUOTES) ?>" data-current="<?= htmlspecialchars($currentDate, ENT_QUOTES) ?>" onclick="openExtendModal(this)" title="Extend validity" aria-label="Extend validity"><i class="fas fa-clock" aria-hidden="true"></i></button>
                                    <button type="button" class="cert-btn is-icon is-danger cert-revoke-btn" data-id="<?= (int) $cert['id'] ?>" data-course="<?= htmlspecialchars($cert['course_title'], ENT_QUOTES) ?>" onclick="confirmRevoke(this)" title="Revoke certificate" aria-label="Revoke certificate"><i class="fas fa-ban" aria-hidden="true"></i></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab 2: Expiry dashboard -->
            <div class="tab-content" data-tab="tab-expiry" role="tabpanel">
                <?php if (empty($expiryData)): ?>
                    <div class="cert-empty">
                        <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                        <strong>No certificates to track</strong>
                        <p>Certificate expiry information will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="cert-panel-head">
                        <label class="cert-select">
                            <select id="expiry-filter" aria-label="Filter by expiry status">
                                <option value="all">All Certificates (<?= count($expiryData) ?>)</option>
                                <option value="warning">Expiring 30d (<?= $stats['expiring_30'] ?>)</option>
                                <option value="critical">Expiring 7d (<?= $stats['expiring_7'] ?>)</option>
                                <option value="expired">Expired (<?= $stats['expired'] ?>)</option>
                                <option value="valid">Valid (<?= $stats['valid'] ?>)</option>
                                <option value="archived">Revoked (<?= $stats['archived'] ?>)</option>
                            </select>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </label>
                        <span class="cert-panel-note" id="expiry-count" aria-live="polite"></span>
                    </div>
                    <div class="cert-table-wrap">
                        <table class="cert-table" id="expiry-table">
                            <thead>
                                <tr>
                                    <th>Learner</th>
                                    <th>Course</th>
                                    <th>Issued</th>
                                    <th>Valid Until</th>
                                    <th class="is-center">Days Left</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expiryData as $cert):
                                    $name = htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']);
                                    $validUntilRaw = $cert['valid_until'] ? date('Y-m-d', strtotime($cert['valid_until'])) : '';
                                    $daysCls = $cert['days_left'] === null ? '' : ($cert['days_left'] <= 7 ? 'is-danger' : ($cert['days_left'] <= 30 ? 'is-warn' : 'is-ok'));
                                ?>
                                <tr class="expiry-row" data-expiry="<?= htmlspecialchars($cert['expiry_status']) ?>" data-search="<?= strtolower($name . ' ' . htmlspecialchars($cert['course_title'])) ?>">
                                    <td>
                                        <div class="cert-cell-name"><?= $name ?></div>
                                        <div class="cert-cell-sub"><?= htmlspecialchars($cert['email']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($cert['course_title']) ?></td>
                                    <td class="cert-cell-muted"><?= $cert['issued_at'] ? date('M j, Y', strtotime($cert['issued_at'])) : 'N/A' ?></td>
                                    <td class="cert-cell-muted"><?= $cert['valid_until'] ? date('M j, Y', strtotime($cert['valid_until'])) : 'No expiry' ?></td>
                                    <td class="is-center">
                                        <?php if ($cert['days_left'] !== null && $cert['status'] === 'active'): ?>
                                            <span class="cert-days <?= $daysCls ?>"><?= $cert['days_left'] > 0 ? $cert['days_left'] . 'd' : 'Expired' ?></span>
                                        <?php else: ?>
                                            <span class="cert-cell-muted">--</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="cert-badge is-<?= htmlspecialchars($cert['expiry_status']) ?>"><?= $expiryStatusLabels[$cert['expiry_status']] ?? 'Unknown' ?></span></td>
                                    <td>
                                        <div class="cert-row-actions">
                                            <a class="cert-btn is-soft" href="?page=public/verify-certificate&amp;back=instructor/certificate&amp;code=<?= htmlspecialchars($cert['verification_code']) ?>"><i class="fas fa-eye" aria-hidden="true"></i> View</a>
                                            <?php if ($cert['status'] === 'active'): ?>
                                            <button type="button" class="cert-btn is-success expiry-extend-btn" data-id="<?= (int) $cert['id'] ?>" data-course="<?= htmlspecialchars($cert['course_title'], ENT_QUOTES) ?>" data-current="<?= htmlspecialchars($validUntilRaw, ENT_QUOTES) ?>" onclick="openExtendModal(this)"><i class="fas fa-calendar-plus" aria-hidden="true"></i> Extend</button>
                                            <button type="button" class="cert-btn is-danger expiry-revoke-btn" data-id="<?= (int) $cert['id'] ?>" data-course="<?= htmlspecialchars($cert['course_title'], ENT_QUOTES) ?>" onclick="confirmRevoke(this)"><i class="fas fa-ban" aria-hidden="true"></i> Revoke</button>
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

            <!-- Tab 3: Users (ranked by certificate count) -->
            <div class="tab-content" data-tab="tab-users" role="tabpanel">
                <?php if (empty($userCerts)): ?>
                    <div class="cert-empty">
                        <i class="fas fa-users" aria-hidden="true"></i>
                        <strong>No certificate holders</strong>
                        <p>Users who earn certificates will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="cert-panel-head">
                        <span class="cert-panel-note">Ranked by certificates earned</span>
                        <label class="cert-select cert-panel-spacer">
                            <select id="users-sort" aria-label="Sort users">
                                <option value="most-certs">Most Certificates</option>
                                <option value="newly-acquired">Newly Acquired</option>
                            </select>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </label>
                    </div>
                    <div class="cert-table-wrap">
                        <table class="cert-table">
                            <thead>
                                <tr>
                                    <th class="is-center">Rank</th>
                                    <th>User</th>
                                    <th class="is-center">Certificates</th>
                                    <th class="is-center">Active</th>
                                    <th>Last Certificate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userCerts as $idx => $uc):
                                    $name = htmlspecialchars($uc['first_name'] . ' ' . $uc['last_name']);
                                    $initials = strtoupper(substr($uc['first_name'], 0, 1) . substr($uc['last_name'], 0, 1));
                                    $rank = $idx + 1;
                                    $rankIcon = $rank === 1 ? 'fas fa-trophy' : ($rank === 2 ? 'fas fa-medal' : ($rank === 3 ? 'fas fa-award' : ''));
                                    $rankClass = $rank <= 3 ? ' is-rank-' . $rank : '';
                                ?>
                                <tr class="user-cert-row<?= $rankClass ?>" data-search="<?= strtolower($name . ' ' . $uc['email']) ?>" data-cert-count="<?= (int) $uc['cert_count'] ?>" data-last-cert="<?= htmlspecialchars((string) $uc['last_cert_date'], ENT_QUOTES) ?>">
                                    <td class="is-center">
                                        <div class="cert-rank"><?php if ($rankIcon): ?><i class="<?= $rankIcon ?>" aria-hidden="true"></i><?php else: ?><?= $rank ?><?php endif; ?></div>
                                    </td>
                                    <td>
                                        <div class="cert-person">
                                            <span class="cert-avatar" aria-hidden="true"><?= $initials ?></span>
                                            <div class="cert-person-text">
                                                <div class="cert-person-name"><?= $name ?></div>
                                                <div class="cert-person-mail"><?= htmlspecialchars($uc['email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="is-center"><span class="cert-count-pill"><?= (int) $uc['cert_count'] ?></span></td>
                                    <td class="is-center"><span class="cert-active-count"><?= (int) $uc['active_count'] ?></span></td>
                                    <td class="cert-cell-muted"><?= certTimeAgo($uc['last_cert_date']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab 4: Templates -->
            <div class="tab-content" data-tab="tab-templates" role="tabpanel">
                <div class="cert-panel-head">
                    <span class="cert-panel-note"><?= count($templates) ?> template<?= count($templates) === 1 ? '' : 's' ?></span>
                    <button type="button" id="btn-add-template" class="cert-btn is-primary is-auto cert-panel-spacer"><i class="fas fa-plus" aria-hidden="true"></i> Add Template</button>
                </div>
                <?php if (empty($templates)): ?>
                    <div class="cert-empty">
                        <i class="fas fa-file-lines" aria-hidden="true"></i>
                        <strong>No templates</strong>
                        <p>Create certificate templates to standardize course completion awards.</p>
                    </div>
                <?php else: ?>
                    <div class="cert-template-grid">
                        <?php foreach ($templates as $t):
                            $isLinked = !empty($t['course_title']);
                        ?>
                        <article class="template-card" data-search="<?= strtolower($t['title'] . ' ' . ($t['course_title'] ?? '')) ?>">
                            <div class="template-head">
                                <div>
                                    <div class="template-title"><?= htmlspecialchars($t['title']) ?></div>
                                    <div class="template-sub"><?= $isLinked ? htmlspecialchars($t['course_title']) : 'General Template' ?></div>
                                </div>
                                <span class="cert-badge is-valid">Active</span>
                            </div>
                            <div class="template-body">
                                <?php if ($t['description']): ?>
                                    <p class="template-desc"><?= htmlspecialchars(mb_substr($t['description'], 0, 150)) ?><?= mb_strlen($t['description']) > 150 ? '...' : '' ?></p>
                                <?php endif; ?>
                                <div class="template-foot">
                                    <span>Created <?= date('M j, Y', strtotime($t['created_at'])) ?></span>
                                    <div class="template-actions">
                                        <button type="button" class="cert-btn is-outline btn-edit-template" data-id="<?= (int) $t['id'] ?>" data-title="<?= htmlspecialchars($t['title'], ENT_QUOTES) ?>" data-desc="<?= htmlspecialchars($t['description'] ?? '', ENT_QUOTES) ?>" data-course="<?= $t['course_id'] ?? '' ?>"><i class="fas fa-pen" aria-hidden="true"></i> Edit</button>
                                        <button type="button" class="cert-btn is-outline btn-delete-template" data-id="<?= (int) $t['id'] ?>" data-title="<?= htmlspecialchars($t['title'], ENT_QUOTES) ?>"><i class="fas fa-trash" aria-hidden="true"></i> Delete</button>
                                    </div>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Modal -->
<div id="confirm-modal-overlay" class="modal-overlay" style="display:none;">
    <div class="cert-modal is-sm" role="dialog" aria-modal="true" aria-describedby="confirm-modal-message">
        <div class="cert-modal-body is-center">
            <div class="cert-modal-icon is-danger"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i></div>
            <p id="confirm-modal-message" class="cert-modal-text"></p>
        </div>
        <div class="cert-modal-foot is-center">
            <button type="button" id="confirm-modal-cancel" class="cert-btn is-soft">Cancel</button>
            <button type="button" id="confirm-modal-ok" class="cert-btn is-danger-solid">Delete</button>
        </div>
    </div>
</div>

<!-- Add / Edit Template Modal -->
<div id="template-modal-overlay" class="modal-overlay" style="display:none;">
    <div class="cert-modal is-md" role="dialog" aria-modal="true" aria-labelledby="template-modal-title">
        <div class="cert-modal-head">
            <h3 id="template-modal-title" class="cert-modal-title">New Certificate Template</h3>
            <button type="button" id="close-template-modal" class="cert-modal-close" aria-label="Close"><i class="fas fa-times" aria-hidden="true"></i></button>
        </div>
        <form id="template-form" data-skip>
            <div class="cert-modal-body">
                <input type="hidden" name="template_id" id="template-form-id" value="" />
                <div class="cert-field">
                    <label for="template-title">Template Title *</label>
                    <input type="text" id="template-title" name="title" required placeholder="e.g. Standard Course Completion" />
                </div>
                <div class="cert-field">
                    <label for="template-description">Description</label>
                    <textarea id="template-description" name="description" rows="3" placeholder="Brief description of this template..."></textarea>
                </div>
                <div class="cert-field is-last">
                    <label for="template-course">Link to Course (optional)</label>
                    <select id="template-course" name="course_id">
                        <option value="">-- General Template --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="cert-modal-foot">
                <button type="button" id="cancel-template-modal" class="cert-btn is-soft">Cancel</button>
                <button type="submit" id="template-form-submit" class="cert-btn is-primary is-auto"><i class="fas fa-save" aria-hidden="true"></i> Create Template</button>
            </div>
        </form>
    </div>
</div>

<!-- Extend Modal -->
<div id="extend-modal-overlay" class="modal-overlay" style="display:none;">
    <div class="cert-modal is-sm" role="dialog" aria-modal="true" aria-labelledby="extend-modal-title">
        <div class="cert-modal-head">
            <h3 id="extend-modal-title" class="cert-modal-title">Extend Certificate</h3>
            <button type="button" class="cert-modal-close" onclick="closeExtendModal()" aria-label="Close"><i class="fas fa-times" aria-hidden="true"></i></button>
        </div>
        <div class="cert-modal-body">
            <p class="cert-field-hint">Set a new expiry date for <strong id="extend-course-name"></strong>.</p>
            <div class="cert-field is-last">
                <label for="extend-date-input">New Expiry Date</label>
                <input type="date" id="extend-date-input" />
            </div>
            <div id="extend-error" class="cert-inline-error is-last"></div>
        </div>
        <div class="cert-modal-foot">
            <button type="button" class="cert-btn is-soft" onclick="closeExtendModal()">Cancel</button>
            <button type="button" id="extend-confirm-btn" class="cert-btn is-success" onclick="submitExtend()">Extend</button>
        </div>
    </div>
</div>

<!-- Revoke Confirm Modal -->
<div id="revoke-modal-overlay" class="modal-overlay" style="display:none;">
    <div class="cert-modal is-sm" role="dialog" aria-modal="true" aria-labelledby="revoke-modal-title">
        <div class="cert-modal-body is-center">
            <div class="cert-modal-icon is-danger"><i class="fas fa-ban" aria-hidden="true"></i></div>
            <h3 id="revoke-modal-title" class="cert-modal-lead">Revoke certificate?</h3>
            <p class="cert-modal-text">This will revoke the certificate for <strong id="revoke-course-name"></strong>. The learner will lose access to this credential.</p>
            <div id="revoke-error" class="cert-inline-error is-left is-last"></div>
        </div>
        <div class="cert-modal-foot is-center">
            <button type="button" class="cert-btn is-soft" onclick="closeRevokeModal()">Cancel</button>
            <button type="button" id="revoke-confirm-btn" class="cert-btn is-danger-solid" onclick="submitRevoke()">Revoke</button>
        </div>
    </div>
</div>

<!-- Certificate Detail Modal -->
<div id="cert-detail-modal" class="modal-overlay" style="display:none;">
    <div class="cert-modal is-lg" role="dialog" aria-modal="true" aria-labelledby="cert-detail-course">
        <div class="cert-detail-hero">
            <button type="button" class="cert-modal-close" onclick="closeCertDetail()" aria-label="Close"><i class="fas fa-times" aria-hidden="true"></i></button>
            <i class="fas fa-award cert-band-icon" aria-hidden="true"></i>
            <h2 id="cert-detail-course" class="cert-detail-course"></h2>
            <div id="cert-detail-status" class="cert-flags"></div>
        </div>
        <div class="cert-detail-body">
            <div class="cert-detail-person">
                <div id="cert-detail-avatar" class="cert-detail-avatar"></div>
                <div>
                    <div id="cert-detail-name" class="cert-detail-name"></div>
                    <div id="cert-detail-email" class="cert-detail-mail"></div>
                </div>
            </div>
            <div class="cert-meta">
                <div>
                    <span class="cert-meta-label">Issued</span>
                    <span id="cert-detail-issued" class="cert-meta-value"></span>
                </div>
                <div>
                    <span class="cert-meta-label">Valid Until</span>
                    <span id="cert-detail-valid" class="cert-meta-value"></span>
                </div>
            </div>
            <div class="cert-detail-code-block">
                <span class="cert-meta-label">Verification Code</span>
                <div id="cert-detail-code" class="cert-code"></div>
            </div>
            <div class="cert-detail-actions">
                <a id="cert-detail-view" class="cert-btn is-primary" href="#"><i class="fas fa-eye" aria-hidden="true"></i> Verify</a>
                <button type="button" id="cert-detail-extend" class="cert-btn is-success"><i class="fas fa-clock" aria-hidden="true"></i> Extend</button>
                <button type="button" id="cert-detail-revoke" class="cert-btn is-danger"><i class="fas fa-ban" aria-hidden="true"></i> Revoke</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var searchInput = document.getElementById('cert-search');
    var statusFilter = document.getElementById('cert-status-filter');
    var expiryFilter = document.getElementById('expiry-filter');
    var countEl = document.getElementById('cert-count');

    function filterAll() {
        var query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        var status = statusFilter ? statusFilter.value : 'all';
        var visibleCards = 0;

        document.querySelectorAll('.cert-card-item').forEach(function(card) {
            var searchText = (card.getAttribute('data-search') || '').toLowerCase();
            var rowStatus = card.getAttribute('data-status') || '';
            var show = (query === '' || searchText.indexOf(query) > -1) && (status === 'all' || rowStatus === status);
            card.style.display = show ? '' : 'none';
            if (show) visibleCards++;
        });

        document.querySelectorAll('.user-cert-row').forEach(function(row) {
            var searchText = (row.getAttribute('data-search') || '').toLowerCase();
            row.style.display = (query === '' || searchText.indexOf(query) > -1) ? '' : 'none';
        });

        document.querySelectorAll('.template-card').forEach(function(card) {
            var searchText = (card.getAttribute('data-search') || '').toLowerCase();
            card.style.display = (query === '' || searchText.indexOf(query) > -1) ? '' : 'none';
        });

        var expiryVal = expiryFilter ? expiryFilter.value : 'all';
        document.querySelectorAll('.expiry-row').forEach(function(row) {
            var searchText = (row.getAttribute('data-search') || '').toLowerCase();
            var expiryStatus = row.getAttribute('data-expiry') || '';
            var show = (query === '' || searchText.indexOf(query) > -1) && (expiryVal === 'all' || expiryStatus === expiryVal);
            row.style.display = show ? '' : 'none';
        });

        if (countEl) countEl.textContent = visibleCards + ' certificate' + (visibleCards === 1 ? '' : 's');
    }

    // Expiry table pagination
    var expiryPageSize = 12;
    var expiryPage = 1;
    function paginateExpiry() {
        var rows = Array.from(document.querySelectorAll('.expiry-row'));
        var efilter = expiryFilter ? expiryFilter.value : 'all';
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

        var note = document.getElementById('expiry-count');
        if (note) note.textContent = vis.length + ' certificate' + (vis.length === 1 ? '' : 's');

        var pg = document.getElementById('expiry-pagination');
        if (pg) {
            pg.querySelector('.page-indicator').textContent = 'Page ' + expiryPage + ' of ' + tot;
            pg.querySelector('[data-action="prev"]').disabled = expiryPage <= 1;
            pg.querySelector('[data-action="next"]').disabled = expiryPage >= tot;
            pg.style.display = tot <= 1 ? 'none' : '';
        }
    }

    function applyFilters() { filterAll(); paginateExpiry(); }

    if (searchInput) searchInput.addEventListener('input', function() { expiryPage = 1; applyFilters(); });
    if (statusFilter) statusFilter.addEventListener('change', applyFilters);
    if (expiryFilter) expiryFilter.addEventListener('change', function() { expiryPage = 1; applyFilters(); });

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
                    return (b.getAttribute('data-last-cert') || '').localeCompare(a.getAttribute('data-last-cert') || '');
                }
                var da = a.getAttribute('data-last-cert') || '';
                var db = b.getAttribute('data-last-cert') || '';
                if (db !== da) return db.localeCompare(da);
                return parseInt(b.getAttribute('data-cert-count') || '0', 10) - parseInt(a.getAttribute('data-cert-count') || '0', 10);
            });

            rows.forEach(function(row, i) {
                tbody.appendChild(row);
                row.classList.remove('is-rank-1', 'is-rank-2', 'is-rank-3');
                var rank = i + 1;
                if (rank <= 3) row.classList.add('is-rank-' + rank);
                var rankDiv = row.querySelector('.cert-rank');
                if (!rankDiv) return;
                if (rank === 1) rankDiv.innerHTML = '<i class="fas fa-trophy" aria-hidden="true"></i>';
                else if (rank === 2) rankDiv.innerHTML = '<i class="fas fa-medal" aria-hidden="true"></i>';
                else if (rank === 3) rankDiv.innerHTML = '<i class="fas fa-award" aria-hidden="true"></i>';
                else rankDiv.textContent = rank;
            });
        });
    }

    // --- Template modal ---
    function openTemplateModal() { document.getElementById('template-modal-overlay').style.display = 'flex'; }
    function closeTemplateModal() {
        document.getElementById('template-modal-overlay').style.display = 'none';
        document.getElementById('template-form').reset();
        document.getElementById('template-form-id').value = '';
        document.getElementById('template-modal-title').textContent = 'New Certificate Template';
        document.getElementById('template-form-submit').innerHTML = '<i class="fas fa-save" aria-hidden="true"></i> Create Template';
    }
    function openEditModal(id, title, desc, courseId) {
        document.getElementById('template-form-id').value = id;
        document.getElementById('template-modal-title').textContent = 'Edit Certificate Template';
        document.getElementById('template-form-submit').innerHTML = '<i class="fas fa-save" aria-hidden="true"></i> Save Changes';
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

    // --- Confirm modal ---
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

    document.querySelectorAll('.btn-edit-template').forEach(function(btn) {
        btn.addEventListener('click', function() {
            openEditModal(btn.getAttribute('data-id'), btn.getAttribute('data-title'), btn.getAttribute('data-desc'), btn.getAttribute('data-course'));
        });
    });

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
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> ' + (isEdit ? 'Saving...' : 'Creating...');
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
            submitBtn.innerHTML = '<i class="fas fa-save" aria-hidden="true"></i> ' + (isEdit ? 'Save Changes' : 'Create Template');
        });
    });

    // --- Extend modal ---
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

    // --- Revoke modal ---
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

    // --- Certificate detail modal ---
    function certStatusTone(expiryStatus, status) {
        if (expiryStatus === 'critical' || expiryStatus === 'expired') return 'is-danger';
        if (expiryStatus === 'warning') return 'is-warn';
        if (expiryStatus === 'archived' || status === 'archived') return 'is-muted';
        return '';
    }
    function certStatusLabel(expiryStatus, status) {
        var labels = { active: 'Active', warning: 'Expiring Soon', critical: 'Critical', expired: 'Expired', archived: 'Revoked', valid: 'Valid' };
        return labels[expiryStatus] || labels[status] || 'Active';
    }
    function openCertDetail(card) {
        var expiryStatus = card.getAttribute('data-expiry') || 'valid';
        var status = card.getAttribute('data-status') || 'active';

        document.getElementById('cert-detail-course').textContent = card.getAttribute('data-course') || 'Certificate';
        document.getElementById('cert-detail-status').innerHTML =
            '<span class="cert-flag ' + certStatusTone(expiryStatus, status) + '">' + certStatusLabel(expiryStatus, status) + '</span>';
        document.getElementById('cert-detail-avatar').textContent = card.getAttribute('data-initials') || '??';
        document.getElementById('cert-detail-name').textContent = card.getAttribute('data-name') || 'Unknown';
        document.getElementById('cert-detail-email').textContent = card.getAttribute('data-email') || '';
        document.getElementById('cert-detail-issued').textContent = card.getAttribute('data-issued') || 'N/A';
        document.getElementById('cert-detail-valid').textContent = card.getAttribute('data-valid') || 'No expiry';
        document.getElementById('cert-detail-code').textContent = card.getAttribute('data-code') || '\u2014';
        document.getElementById('cert-detail-view').href = card.getAttribute('data-verify') || '#';

        var extendBtn = card.querySelector('.cert-extend-btn');
        var revokeBtn = card.querySelector('.cert-revoke-btn');
        var extendBtnModal = document.getElementById('cert-detail-extend');
        var revokeBtnModal = document.getElementById('cert-detail-revoke');
        if (extendBtn) {
            extendBtnModal.style.display = '';
            extendBtnModal.onclick = function() { extendBtn.click(); closeCertDetail(); };
        } else {
            extendBtnModal.style.display = 'none';
        }
        if (revokeBtn) {
            revokeBtnModal.style.display = '';
            revokeBtnModal.onclick = function() { revokeBtn.click(); closeCertDetail(); };
        } else {
            revokeBtnModal.style.display = 'none';
        }

        document.getElementById('cert-detail-modal').style.display = 'flex';
    }
    function closeCertDetail() {
        document.getElementById('cert-detail-modal').style.display = 'none';
    }
    window.closeCertDetail = closeCertDetail;

    document.body.addEventListener('click', function(e) {
        var card = e.target.closest('.cert-card-item');
        if (!card) return;
        if (e.target.closest('a, button')) return;
        openCertDetail(card);
    });
    document.body.addEventListener('keydown', function(e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        if (!e.target.closest) return;
        var card = e.target.closest('.cert-card-item');
        if (!card || e.target.closest('a, button')) return;
        e.preventDefault();
        openCertDetail(card);
    });
    document.getElementById('cert-detail-modal').addEventListener('click', function(e) {
        if (e.target === this) closeCertDetail();
    });

    applyFilters();
})();
</script>
