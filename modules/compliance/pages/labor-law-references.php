<?php

$pageTitle = 'Labor Law References';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$currentEmployeeId = (int) ($_SESSION['employee_id'] ?? 0);
$currentRole       = (int) ($_SESSION['role'] ?? 0);
$canManage         = in_array($currentRole, [1, 2, 3], true);

require_once __DIR__ . '/../classes/LaborLawReference.php';

if (!isset($db)) {
    if (class_exists('Database')) {
        $db = (new Database())->getConnection();
    } else {
        require_once __DIR__ . '/../../../database/db.php';
        $db = (new Database())->getConnection();
    }
}

$model = new LaborLawReference($db);

$search = trim((string) ($_GET['search'] ?? ''));

$references = $model->getReferences(array_filter([
    'search' => $search,
]));

$categories = $model->getAllCategories();
$categoryMap = [];
foreach ($categories as $cat) {
    $categoryMap[$cat['id']] = $cat['name'];
}

usort($references, function ($a, $b) {
    $isLaborCodeA = (($a['reference_number'] ?? '') === 'PD 442' || (($a['reference_type'] ?? '') === 'Labor Code Provision'));
    $isLaborCodeB = (($b['reference_number'] ?? '') === 'PD 442' || (($b['reference_type'] ?? '') === 'Labor Code Provision'));

    if ($isLaborCodeA && !$isLaborCodeB) {
        return -1;
    }
    if (!$isLaborCodeA && $isLaborCodeB) {
        return 1;
    }

    $dateA = $a['date_issued'] ?? $a['created_at'] ?? '';
    $dateB = $b['date_issued'] ?? $b['created_at'] ?? '';
    return strcmp($dateB, $dateA);
});

$perPage = 10;
$page = isset($_GET['page_num']) ? max(1, (int) $_GET['page_num']) : 1;
$totalItems = count($references);
$totalPages = (int) ceil($totalItems / $perPage);
if ($totalPages < 1) $totalPages = 1;
if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;
$paginatedReferences = array_slice($references, $offset, $perPage);

function llr_build_page_url(int $pageNum, string $search): string {
    $params = ['page' => 'labor-law-references', 'page_num' => $pageNum];
    if ($search !== '') {
        $params['search'] = $search;
    }
    return '?' . http_build_query($params);
}
?>
<style>
/* ============================================
   Labor Law References — Minimal Styles
   ============================================ */

.llr-module {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
    padding: 4px 2px 24px;
}

.llr-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 16px;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
}

.llr-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    width: 100%;
    min-width: 0;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.llr-card-head h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
    min-width: 0;
    letter-spacing: -0.01em;
}

.llr-empty {
    padding: 32px 24px;
    text-align: center;
    color: #6b7280;
    font-size: 0.875rem;
    min-width: 0;
    box-sizing: border-box;
}

.llr-list {
    display: flex;
    flex-direction: column;
    gap: 1px;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    background: #e5e7eb;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
}

.llr-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 16px 20px;
    background: #fff;
    text-decoration: none;
    color: inherit;
    transition: background 0.1s ease;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
}

.llr-item:hover {
    background: #f9fafb;
}

.llr-item-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    width: 100%;
    min-width: 0;
}

.llr-item-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #111827;
    line-height: 1.4;
    min-width: 0;
    overflow-wrap: anywhere;
    word-break: break-word;
    letter-spacing: -0.005em;
}

.llr-item-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.llr-item-ref {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #4b5563;
    min-width: 0;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.llr-item-date {
    font-size: 0.8125rem;
    color: #6b7280;
    min-width: 0;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.llr-item-preview {
    font-size: 0.875rem;
    color: #4b5563;
    line-height: 1.5;
    display: -webkit-box;
    line-clamp: 2;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-width: 0;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.llr-item-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
    flex-wrap: wrap;
}

.llr-stamp {
    display: inline-block;
    font-size: 0.6875rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 3px;
    white-space: nowrap;
    min-width: 0;
    overflow-wrap: anywhere;
    word-break: break-word;
    letter-spacing: 0.01em;
}

.llr-stamp-active {
    background: #ecfdf5;
    color: #065f46;
}

.llr-stamp-amended {
    background: #fffbeb;
    color: #92400e;
}

.llr-stamp-superseded {
    background: #eff6ff;
    color: #1e40af;
}

.llr-stamp-repealed {
    background: #fef2f2;
    color: #991b1b;
}

.llr-stamp-archived {
    background: #f9fafb;
    color: #374151;
}

.llr-stamp-for_reference {
    background: #f9fafb;
    color: #4b5563;
}

.llr-table-search {
    position: relative;
    flex-shrink: 0;
}

.llr-table-search input {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 0.8125rem;
    outline: none;
    width: 240px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    min-width: 0;
    box-sizing: border-box;
    color: #111827;
    background: #fff;
}

.llr-table-search input:focus {
    border-color: #111827;
    box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.05);
}

.llr-table-search input::placeholder {
    color: #9ca3af;
}

.llr-table-search .llr-search-clear {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 2px;
    font-size: 1.125rem;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.llr-table-search .llr-search-clear:hover {
    color: #111827;
}

.llr-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 4px;
    border: 1px solid var(--color1, #200082);
    background: var(--color1, #200082);
    color: #fff;
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s ease;
    text-decoration: none;
    min-height: 36px;
    letter-spacing: 0.01em;
}

.llr-action-btn:hover {
    background: #1a0066;
    border-color: #1a0066;
    color: #fff;
}

.llr-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 20px;
    flex-wrap: wrap;
    width: 100%;
    min-width: 0;
}

.llr-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 12px;
    border-radius: 4px;
    border: 1px solid #d1d5db;
    background: #fff;
    color: #374151;
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
    min-height: 36px;
    letter-spacing: 0.01em;
}

.llr-page-btn:hover:not(:disabled) {
    border-color: #111827;
    color: #111827;
    background: #f9fafb;
}

.llr-page-btn:disabled {
    background: #f3f4f6;
    border-color: #e5e7eb;
    color: #9ca3af;
    cursor: not-allowed;
}

.llr-page-btn.active {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
    font-weight: 600;
}

.llr-page-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    color: #9ca3af;
    font-size: 0.8125rem;
    min-height: 36px;
}

.llr-card-body {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
}

@media (max-width: 1100px) {
    .llr-table-search input {
        width: 220px;
    }
}

@media (max-width: 768px) {
    .llr-module {
        padding: 4px 2px 20px;
    }

    .llr-card-head {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
        padding-bottom: 14px;
    }

    .llr-card-head h3 {
        width: 100%;
        font-size: 0.9375rem;
    }

    .llr-table-search {
        width: 100%;
    }

    .llr-table-search input {
        width: 100%;
        min-width: 0;
    }

    .llr-item {
        padding: 14px 16px;
    }

    .llr-card {
        padding: 16px;
        border-radius: 4px;
    }

    .llr-page-btn {
        min-width: 40px;
        min-height: 40px;
    }

    .llr-page-ellipsis {
        min-width: 40px;
        min-height: 40px;
    }

    .llr-action-btn {
        min-height: 40px;
        padding: 8px 14px;
    }
}

@media (max-width: 576px) {
    .llr-item {
        padding: 14px 16px;
    }

    .llr-card {
        padding: 16px;
    }

    .llr-item-title {
        font-size: 0.875rem;
    }

    .llr-item-meta {
        gap: 3px;
    }

    .llr-item-ref,
    .llr-item-date {
        font-size: 0.78125rem;
    }
}

@media (max-width: 380px) {
    .llr-card {
        padding: 14px;
        border-radius: 3px;
    }

    .llr-item {
        padding: 12px 14px;
    }

    .llr-item-title {
        font-size: 0.8125rem;
    }

    .llr-item-ref,
    .llr-item-date {
        font-size: 0.75rem;
    }

    .llr-item-preview {
        font-size: 0.8125rem;
    }

    .llr-card-head h3 {
        font-size: 0.875rem;
    }

    .llr-table-search input {
        font-size: 0.78125rem;
    }

    .llr-action-btn {
        font-size: 0.75rem;
        min-height: 38px;
    }

    .llr-page-btn {
        min-width: 38px;
        min-height: 38px;
        font-size: 0.78125rem;
        padding: 0 10px;
    }

    .llr-page-ellipsis {
        min-width: 38px;
        min-height: 38px;
        font-size: 0.78125rem;
    }
}
</style>

<section class="llr-module">
    <div class="llr-card">
        <form class="llr-table-search" method="get" action="" data-skip>
            <input type="hidden" name="page" value="labor-law-references">
            <input type="hidden" name="page_num" value="1">
            <input type="text" name="search" placeholder="Search references..." value="<?= htmlspecialchars($search) ?>" aria-label="Search labor law references">
            <?php if ($search !== ''): ?>
                <a href="?page=labor-law-references" class="llr-search-clear" title="Clear search" aria-label="Clear search">&times;</a>
            <?php endif; ?>
        </form>
        <div class="llr-card-body">
            <?php if (empty($paginatedReferences)): ?>
                <div class="llr-empty">No labor law references found.</div>
            <?php else: ?>
            <div class="llr-list">
                <?php foreach ($paginatedReferences as $ref):
                    $preview = '';
                    if (!empty($ref['summary'])) {
                        $preview = $ref['summary'];
                    } elseif (!empty($ref['description'])) {
                        $preview = $ref['description'];
                    }
                    $preview = trim($preview);
                    if ($preview !== '') {
                        $preview = mb_strimwidth($preview, 0, 150, '...');
                    }

                    $pdfHref = '';
                    $pdfLabel = '';
                    if (!empty($ref['document_path'])) {
                        $pdfHref = $ref['document_path'];
                        if (str_starts_with($pdfHref, 'C:/xampp/htdocs/hrms-capstone/')) {
                            $pdfHref = '/hrms-capstone/' . ltrim(substr($pdfHref, strlen('C:/xampp/htdocs/hrms-capstone/')), '/');
                        } elseif (strpos($pdfHref, '://') === false && strpos($pdfHref, '/') !== 0) {
                            $pdfHref = '/hrms-capstone/' . ltrim($pdfHref, '/');
                        }

                        $documentName = basename((string) $ref['document_path']);
                        $pdfLabel = $documentName !== '' ? $documentName : 'View Document';
                    }
                ?>
                <div class="llr-item">
                    <div class="llr-item-head">
                        <div class="llr-item-title"><?= htmlspecialchars($ref['title'] ?? 'Untitled Reference') ?></div>
                    </div>
                    <div class="llr-item-meta">
                        <span class="llr-item-ref"><?= htmlspecialchars($ref['reference_type'] ?? '') ?></span>
                        <?php if (!empty($ref['reference_number'])): ?>
                            <span class="llr-item-ref"><?= htmlspecialchars($ref['reference_number']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($ref['date_issued']) || $pdfHref !== ''): ?>
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <?php if (!empty($ref['date_issued'])): ?>
                                    <span class="llr-item-date">Issued: <?= htmlspecialchars($ref['date_issued']) ?></span>
                                <?php endif; ?>
                                <?php if ($pdfHref !== ''): ?>
                                    <a href="<?= htmlspecialchars($pdfHref) ?>" target="_blank" rel="noopener noreferrer" class="llr-item-ref" style="text-decoration:underline;"><?= htmlspecialchars($pdfLabel) ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($preview !== ''): ?>
                        <div class="llr-item-preview"><?= htmlspecialchars($preview) ?></div>
                    <?php endif; ?>
                    <div class="llr-item-actions">
                        <a href="?page=labor-law-reference-detail&id=<?= (int) ($ref['id'] ?? 0) ?>" class="llr-action-btn">View Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="llr-pagination" id="llrPagination">
                <?php if ($page > 1): ?>
                    <a class="llr-page-btn" href="<?= htmlspecialchars(llr_build_page_url($page - 1, $search)) ?>" aria-label="Previous page">Prev</a>
                <?php else: ?>
                    <button class="llr-page-btn" disabled aria-label="Previous page">Prev</button>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                if ($startPage > 1) {
                    echo '<a class="llr-page-btn" href="' . htmlspecialchars(llr_build_page_url(1, $search)) . '" aria-label="Page 1">1</a>';
                    if ($startPage > 2) echo '<span class="llr-page-ellipsis" aria-hidden="true">...</span>';
                }
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <a class="llr-page-btn <?= $i === $page ? 'active' : '' ?>" href="<?= htmlspecialchars(llr_build_page_url($i, $search)) ?>" aria-label="<?= 'Page ' . (int) $i ?>" <?= $i === $page ? 'aria-current="page"' : '' ?>>
                        <?= (int) $i ?>
                    </a>
                <?php endfor;
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) echo '<span class="llr-page-ellipsis" aria-hidden="true">...</span>';
                    echo '<a class="llr-page-btn" href="' . htmlspecialchars(llr_build_page_url($totalPages, $search)) . '" aria-label="Page ' . (int) $totalPages . '">' . (int) $totalPages . '</a>';
                }
                ?>

                <?php if ($page < $totalPages): ?>
                    <a class="llr-page-btn" href="<?= htmlspecialchars(llr_build_page_url($page + 1, $search)) ?>" aria-label="Next page">Next</a>
                <?php else: ?>
                    <button class="llr-page-btn" disabled aria-label="Next page">Next</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<script src="js/pages/labor-law-references.js"></script>


