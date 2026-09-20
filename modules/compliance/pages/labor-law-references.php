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
.llr-module {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
    padding: 4px 2px 24px;
}

.llr-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border, #e4e8ee);
    border-radius: 14px;
    padding: 14px;
    box-shadow: var(--shadow-soft, 0 1px 2px rgba(13, 27, 46, .04));
    margin-bottom: 14px;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
}

.llr-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 14px;
    flex-wrap: wrap;
    width: 100%;
    min-width: 0;
}

.llr-card-head h3 {
    margin: 0;
    font-size: 0.98rem;
    font-weight: 700;
    color: var(--text-900, #1b2430);
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.llr-empty {
    padding: 24px;
    text-align: center;
    color: var(--text-400, #8b93a1);
    font-size: 0.84rem;
    min-width: 0;
    box-sizing: border-box;
}

.llr-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;
    max-width: 100%;
    min-width: 0;
}

.llr-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 14px;
    border: 1px solid var(--border, #e4e8ee);
    border-radius: 10px;
    background: #fff;
    text-decoration: none;
    color: inherit;
    transition: all .15s ease;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
}

.llr-item:hover {
    border-color: var(--info-blue, #3b82c4);
    box-shadow: 0 0 0 3px rgba(59, 130, 196, .08);
}

.llr-item-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
    width: 100%;
    min-width: 0;
}

.llr-item-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--text-900, #1b2430);
    line-height: 1.3;
    min-width: 0;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.llr-item-meta {
    display: flex;
    align-items: center;
    gap: 6px 10px;
    flex-wrap: wrap;
    min-width: 0;
}

.llr-item-ref {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--text-600, #5b6472);
    min-width: 0;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.llr-item-date {
    font-size: 0.78rem;
    color: var(--text-500, #6b7280);
    min-width: 0;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.llr-item-preview {
    font-size: 0.82rem;
    color: var(--text-600, #5b6472);
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
    margin-top: 4px;
    flex-wrap: wrap;
}

.llr-stamp {
    display: inline-block;
    font-size: 0.66rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
    white-space: nowrap;
    min-width: 0;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.llr-stamp-active {
    background: rgba(47, 158, 110, .12);
    color: #1f7a52;
}

.llr-stamp-amended {
    background: rgba(217, 154, 43, .14);
    color: #a86b13;
}

.llr-stamp-superseded {
    background: rgba(59, 130, 196, .12);
    color: #1c5a8a;
}

.llr-stamp-repealed {
    background: rgba(214, 72, 74, .12);
    color: #a3272a;
}

.llr-stamp-archived {
    background: rgba(107, 114, 128, .12);
    color: #4b5563;
}

.llr-stamp-for_reference {
    background: rgba(107, 114, 128, .1);
    color: #6b7280;
}

.llr-table-search {
    position: relative;
    flex-shrink: 0;
}

.llr-table-search i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-400, #8b93a1);
    font-size: 0.8rem;
    pointer-events: none;
}

.llr-table-search input {
    padding: 7px 36px 7px 30px;
    border: 1px solid var(--border, #e4e8ee);
    border-radius: 8px;
    font-size: 0.78rem;
    outline: none;
    width: 220px;
    transition: border-color .15s ease, box-shadow .15s ease;
    min-width: 0;
    box-sizing: border-box;
}

.llr-table-search input:focus {
    border-color: var(--seal-gold, #a8791f);
    box-shadow: 0 0 0 3px rgba(168, 121, 31, .12);
}

.llr-table-search .llr-search-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1rem;
    color: var(--text-400, #8b93a1);
    text-decoration: none;
    line-height: 1;
    min-width: 32px;
    min-height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px;
    border-radius: 4px;
}

.llr-table-search .llr-search-clear:hover {
    color: var(--text-900, #1b2430);
}

.llr-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid var(--border, #e4e8ee);
    background: #fff;
    color: var(--text-700, #3b4252);
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: all .15s ease;
    text-decoration: none;
    min-height: 36px;
}

.llr-action-btn:hover {
    border-color: var(--info-blue, #3b82c4);
    color: var(--info-blue, #3b82c4);
}

.llr-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 16px;
    flex-wrap: wrap;
    width: 100%;
    min-width: 0;
}

.llr-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border-radius: 8px;
    border: 1px solid var(--border, #e4e8ee);
    background: #fff;
    color: var(--text-700, #3b4252);
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all .15s ease;
    min-height: 34px;
}

.llr-page-btn:hover:not(:disabled) {
    border-color: var(--info-blue, #3b82c4);
    color: var(--info-blue, #3b82c4);
}

.llr-page-btn:disabled {
    background: var(--paper, #eef1f5);
    border-color: var(--hairline, #dde3ea);
    color: var(--text-400, #8b95a4);
    cursor: not-allowed;
}

.llr-page-btn.active {
    background: var(--info-blue, #3b82c4);
    border-color: var(--info-blue, #3b82c4);
    color: #fff;
}

.llr-page-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    color: var(--text-400, #8b93a1);
    font-size: 0.82rem;
    min-height: 34px;
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
        gap: 10px;
    }

    .llr-card-head h3 {
        width: 100%;
    }

    .llr-table-search {
        width: 100%;
    }

    .llr-table-search input {
        width: 100%;
        min-width: 0;
        padding-right: 36px;
    }

    .llr-item {
        padding: 12px;
    }

    .llr-card {
        padding: 12px;
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
        padding: 12px;
    }

    .llr-card {
        padding: 12px;
    }

    .llr-item-title {
        font-size: 0.9rem;
    }

    .llr-item-meta {
        gap: 4px 8px;
    }

    .llr-item-ref,
    .llr-item-date {
        font-size: 0.76rem;
    }
}

@media (max-width: 380px) {
    .llr-card {
        padding: 10px;
        border-radius: 12px;
    }

    .llr-item {
        padding: 10px;
        border-radius: 8px;
    }

    .llr-item-title {
        font-size: 0.86rem;
    }

    .llr-item-ref,
    .llr-item-date {
        font-size: 0.73rem;
    }

    .llr-item-preview {
        font-size: 0.78rem;
    }

    .llr-card-head h3 {
        font-size: 0.92rem;
    }

    .llr-table-search input {
        font-size: 0.76rem;
    }

    .llr-action-btn {
        font-size: 0.73rem;
        min-height: 38px;
    }

    .llr-page-btn {
        min-width: 36px;
        min-height: 36px;
        font-size: 0.78rem;
        padding: 0 8px;
    }

    .llr-page-ellipsis {
        min-width: 36px;
        min-height: 36px;
        font-size: 0.78rem;
    }
}
</style>

<section class="llr-module">
    <div class="llr-card">
        <div class="llr-card-head">
            <h3><i class="bi bi-bank2"></i> Labor Law References</h3>
            <form class="llr-table-search" method="get" action="" data-skip>
                <input type="hidden" name="page" value="labor-law-references">
                <input type="hidden" name="page_num" value="1">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="text" name="search" placeholder="Search references..." value="<?= htmlspecialchars($search) ?>" aria-label="Search labor law references">
                <?php if ($search !== ''): ?>
                    <a href="?page=labor-law-references" class="llr-search-clear" title="Clear search" aria-label="Clear search">&times;</a>
                <?php endif; ?>
            </form>
        </div>
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
                        <?php if (!empty($ref['date_issued'])): ?>
                            <span class="llr-item-date">Issued: <?= htmlspecialchars($ref['date_issued']) ?></span>
                        <?php endif; ?>
                        <?php if ($pdfHref !== ''): ?>
                            <span class="llr-item-ref">&bull;</span>
                            <a href="<?= htmlspecialchars($pdfHref) ?>" target="_blank" rel="noopener noreferrer" class="llr-item-ref" style="text-decoration:underline;"><?= htmlspecialchars($pdfLabel) ?></a>
                        <?php endif; ?>
                    </div>
                    <?php if ($preview !== ''): ?>
                        <div class="llr-item-preview"><?= htmlspecialchars($preview) ?></div>
                    <?php endif; ?>
                    <div class="llr-item-actions">
                        <a href="?page=labor-law-reference-detail&id=<?= (int) ($ref['id'] ?? 0) ?>" class="llr-action-btn">
                            <i class="bi bi-eye"></i> View Details
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="llr-pagination" id="llrPagination">
                <?php if ($page > 1): ?>
                    <a class="llr-page-btn" href="<?= htmlspecialchars(llr_build_page_url($page - 1, $search)) ?>" aria-label="Previous page">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <button class="llr-page-btn" disabled aria-label="Previous page"><i class="bi bi-chevron-left"></i></button>
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
                    <a class="llr-page-btn" href="<?= htmlspecialchars(llr_build_page_url($page + 1, $search)) ?>" aria-label="Next page">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <button class="llr-page-btn" disabled aria-label="Next page"><i class="bi bi-chevron-right"></i></button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<script src="js/pages/labor-law-references.js"></script>
