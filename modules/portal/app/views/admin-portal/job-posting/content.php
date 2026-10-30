<?php
// Safely escape output.
$escape = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

// Decode JSON columns safely.
$decodeJson = static function ($value): array {
    if (is_array($value)) {
        return $value;
    }

    if (!is_string($value) || trim($value) === '') {
        return [];
    }

    $decoded = json_decode($value, true);

    return is_array($decoded) ? $decoded : [];
};

$totalJobPostings = count($jobPostings ?? []);
?>

<div class="employee-dashboard">

    <!-- TABLE HEADER -->
    <div style="
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:15px;
        flex-wrap:wrap;
        margin-bottom:15px;
    ">
        <div>
            <h3 style="
                margin:0 0 5px;
                color:#111827;
                font-size:40px;
                font-weight:700;
            ">
                <i class="fas fa-briefcase" style="color:#2563eb;margin-right:7px;"></i>
                Job Posting Management
            </h3>

            <p style="margin:0;color:#6b7280;font-size:13px;">
                Create, manage, and monitor company job postings and available positions.
            </p>
        </div>

        <div style="
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
        ">
            <span style="
                display:inline-flex;
                align-items:center;
                gap:6px;
                padding:7px 13px;
                border-radius:20px;
                background:#eff6ff;
                color:#2563eb;
                font-size:12px;
                font-weight:600;
            ">
                <i class="fas fa-clipboard-list"></i>
                <?= $totalJobPostings ?> Job Postings
            </span>

            <!-- CREATE BUTTON -->
            <button type="button" class="job-create-btn" data-bs-toggle="modal" data-bs-target="#jobPostingModal">

                <i class="fas fa-plus"></i>
                Create
            </button>
        </div>
    </div>

    <?php if (!empty($jobPostings)): ?>

        <!-- TABLE CONTAINER -->
        <div class="job-table-wrapper">
            <?php require __DIR__ . '/../../partials/notification.php'; ?>

            <div class="job-table-scroll" id="jobPostingTable">

                <table class="job-posting-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Open Positions</th>
                            <th>Qualifications</th>
                            <th>Benefits</th>
                            <th>Company Email</th>
                            <th>Address</th>
                            <th>Contact Number</th>
                        </tr>
                    </thead>

                    <tbody id="jobPostingTableBody">

                        <?php foreach ($jobPostings as $job): ?>

                            <?php
                            $positions = $decodeJson($job['open_positions'] ?? []);
                            $qualifications = $decodeJson($job['qualifications'] ?? []);
                            $benefits = $decodeJson($job['benefits'] ?? []);

                            $companyName = $job['comp_name'] ?? 'Unnamed Company';
                            $companyLogo = trim((string) ($job['comp_logo'] ?? ''));

                            // Use the asset helper for company logos.
                            $logoUrl = !empty($companyLogo)
                                ? asset(ltrim($companyLogo, '/'))
                                : '';
                            ?>

                            <tr class="job-posting-row">

                                <!-- COMPANY -->
                                <td>
                                    <div class="job-company">
                                        <?php if (!empty($logoUrl)): ?>
                                            <img src="<?= $escape($logoUrl) ?>" alt="<?= $escape($companyName) ?>"
                                                class="job-company-logo"
                                                onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                            <div class="job-company-fallback" style="display:none;">
                                                <i class="fas fa-building"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="job-company-fallback">
                                                <i class="fas fa-building"></i>
                                            </div>
                                        <?php endif; ?>

                                        <div class="job-company-info">
                                            <strong><?= $escape($companyName) ?></strong>
                                            <small>Company</small>
                                        </div>
                                    </div>
                                </td>

                                <!-- OPEN POSITIONS -->
                                <td style="padding:15px 16px;min-width:220px;border-bottom:1px solid #f1f5f9;">

                                    <?php if (!empty($positions)): ?>

                                        <div style="display:flex;flex-direction:column;gap:8px;">

                                            <?php foreach ($positions as $position): ?>

                                                <?php
                                                $positionTitle = is_array($position)
                                                    ? ($position['title'] ?? 'Untitled Position')
                                                    : $position;

                                                $employmentType = is_array($position)
                                                    ? ($position['employment_type'] ?? '')
                                                    : '';

                                                $vacancies = is_array($position)
                                                    ? ($position['vacancies'] ?? null)
                                                    : null;
                                                ?>

                                                <div style="padding:7px 9px;border-radius:7px;background:#eff6ff;">

                                                    <div style="color:#1e40af;font-weight:600;">
                                                        <?= htmlspecialchars((string) $positionTitle, ENT_QUOTES, 'UTF-8') ?>
                                                    </div>

                                                    <small style="color:#64748b;">

                                                        <?php if ($employmentType): ?>
                                                            <?= htmlspecialchars($employmentType, ENT_QUOTES, 'UTF-8') ?>
                                                        <?php endif; ?>

                                                        <?php if ($vacancies !== null): ?>
                                                            <?= $employmentType ? ' • ' : '' ?>
                                                            <?= htmlspecialchars((string) $vacancies, ENT_QUOTES, 'UTF-8') ?> Vacancies
                                                        <?php endif; ?>

                                                    </small>

                                                </div>

                                            <?php endforeach; ?>

                                        </div>

                                    <?php else: ?>

                                        <span style="color:#9ca3af;">No positions listed</span>

                                    <?php endif; ?>

                                </td>

                                <!-- QUALIFICATIONS -->
                                <td>
                                    <?php if (!empty($qualifications)): ?>
                                        <ul style="
            list-style-type:disc;
            margin:0;
            padding-left:20px;
            min-width:160px;
            max-width:230px;
        ">
                                            <?php foreach ($qualifications as $qualification): ?>
                                                <li style="
                    display:list-item;
                    margin-bottom:6px;
                    color:#4b5563;
                    font-size:12px;
                    overflow-wrap:anywhere;
                ">
                                                    <?= $escape(
                                                        is_array($qualification)
                                                        ? ($qualification['name'] ?? $qualification['title'] ?? '')
                                                        : $qualification
                                                    ) ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span style="color:#9ca3af;font-size:12px;font-style:italic;">
                                            No qualifications listed
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- BENEFITS -->
                                <td>
                                    <?php if (!empty($benefits)): ?>
                                        <ul class="job-detail-list job-benefit-list">
                                            <?php foreach ($benefits as $benefit): ?>
                                                <li>
                                                    <i class="fas fa-check-circle"></i>
                                                    <?= $escape(
                                                        is_array($benefit)
                                                        ? ($benefit['name'] ?? $benefit['title'] ?? '')
                                                        : $benefit
                                                    ) ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="job-empty-value">No benefits listed</span>
                                    <?php endif; ?>
                                </td>

                                <!-- EMAIL -->
                                <td>
                                    <?php if (!empty($job['comp_email'])): ?>
                                        <a class="job-contact-link" href="mailto:<?= $escape($job['comp_email']) ?>">
                                            <i class="fas fa-envelope"></i>
                                            <?= $escape($job['comp_email']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="job-empty-value">Not provided</span>
                                    <?php endif; ?>
                                </td>

                                <!-- ADDRESS -->
                                <td>
                                    <div class="job-contact-text">
                                        <?php if (!empty($job['comp_address'])): ?>
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?= $escape($job['comp_address']) ?>
                                        <?php else: ?>
                                            <span class="job-empty-value">Not provided</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- CONTACT NUMBER -->
                                <td>
                                    <?php if (!empty($job['comp_number'])): ?>
                                        <div class="job-contact-text">
                                            <i class="fas fa-phone"></i>
                                            <?= $escape($job['comp_number']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="job-empty-value">Not provided</span>
                                    <?php endif; ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

            <!-- TABLE FOOTER / PAGINATION -->
            <div class="job-table-footer">
                <div class="job-pagination-info" id="jobPaginationInfo">
                    Showing 0 to 0 of <?= $totalJobPostings ?> entries
                </div>

                <div class="job-pagination" id="jobPagination">
                    <button type="button" id="jobPrevPage" class="job-page-btn">
                        <i class="fas fa-chevron-left"></i>
                        Previous
                    </button>

                    <div id="jobPageNumbers" class="job-page-numbers"></div>

                    <button type="button" id="jobNextPage" class="job-page-btn">
                        Next
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

        </div>

    <?php else: ?>

        <!-- EMPTY STATE -->
        <div class="job-empty-state">
            <div class="job-empty-icon">
                <i class="fas fa-briefcase"></i>
            </div>

            <h3>No Job Postings Found</h3>

            <p>
                There are currently no company job postings.
                Create your first job posting to get started.
            </p>

            <a href="<?= htmlspecialchars(appUrl('index.php?url=admin-job-posting-create'), ENT_QUOTES, 'UTF-8') ?>"
                class="job-create-btn">
                <i class="fas fa-plus"></i>
                Create Job Posting
            </a>
        </div>

    <?php endif; ?>
</div>
<?php require __DIR__ . '/create.php'; ?>
<style>
    /* CREATE BUTTON */
    .job-create-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 16px;
        background: #2563eb;
        color: #fff !important;
        border: 1px solid #2563eb;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none !important;
        white-space: nowrap;
        transition: background .2s ease, transform .2s ease;
    }

    .job-create-btn:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
    }

    /* TABLE WRAPPER */
    .job-table-wrapper {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 3px 12px rgba(0, 0, 0, .04);
    }

    /* HORIZONTAL SWIPE / DRAG */
    .job-table-scroll {
        display: block;
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
        touch-action: pan-x pan-y;
        cursor: grab;
        scrollbar-width: thin;
        scrollbar-color: #9ca3af #f3f4f6;
    }

    .job-table-scroll.is-dragging {
        cursor: grabbing;
        user-select: none;
    }

    .job-table-scroll::-webkit-scrollbar {
        height: 9px;
    }

    .job-table-scroll::-webkit-scrollbar-track {
        background: #f3f4f6;
    }

    .job-table-scroll::-webkit-scrollbar-thumb {
        background: #9ca3af;
        border-radius: 10px;
    }

    .job-table-scroll::-webkit-scrollbar-thumb:hover {
        background: #6b7280;
    }

    /* TABLE */
    .job-posting-table {
        width: 100%;
        min-width: 1250px;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
    }

    .job-posting-table thead th {
        padding: 14px 16px;
        background: #f9fafb;
        color: #374151;
        font-size: 11px;
        font-weight: 700;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
        border-bottom: 1px solid #e5e7eb;
    }

    .job-posting-table tbody td {
        padding: 16px;
        color: #4b5563;
        vertical-align: top;
        border-bottom: 1px solid #f1f5f9;
        line-height: 1.5;
    }

    .job-posting-table tbody tr:last-child td {
        border-bottom: none;
    }

    .job-posting-table tbody tr:hover {
        background: #fafcff;
    }

    /* COMPANY */
    .job-company {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 170px;
    }

    .job-company-logo,
    .job-company-fallback {
        width: 42px;
        height: 42px;
        flex-shrink: 0;
        border-radius: 9px;
        object-fit: contain;
        border: 1px solid #e5e7eb;
        background: #fff;
    }

    .job-company-fallback {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        background: #eff6ff;
        font-size: 17px;
    }

    .job-company-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .job-company-info strong {
        color: #111827;
        font-size: 13px;
        font-weight: 700;
    }

    .job-company-info small {
        color: #9ca3af;
        font-size: 11px;
    }

    /* POSITIONS */
    .job-list {
        display: flex;
        flex-direction: column;
        gap: 9px;
        min-width: 150px;
    }

    .job-list-item {
        display: flex;
        flex-direction: column;
        gap: 2px;
        padding-left: 9px;
        border-left: 3px solid #bfdbfe;
    }

    .job-list-item strong {
        color: #1f2937;
        font-size: 12px;
    }

    .job-list-item small {
        color: #6b7280;
        font-size: 11px;
    }

    /* QUALIFICATIONS / BENEFITS */
    .job-detail-list {
        margin: 0;
        padding-left: 17px;
        min-width: 160px;
        max-width: 230px;
    }

    .job-detail-list li {
        margin-bottom: 6px;
        color: #4b5563;
        font-size: 12px;
        overflow-wrap: anywhere;
    }

    .job-benefit-list {
        list-style: none;
        padding: 0;
    }

    .job-benefit-list li {
        display: flex;
        align-items: flex-start;
        gap: 7px;
    }

    .job-benefit-list li i {
        margin-top: 3px;
        color: #16a34a;
        font-size: 11px;
    }

    /* CONTACT DETAILS */
    .job-contact-link,
    .job-contact-text {
        display: flex;
        align-items: flex-start;
        gap: 7px;
        min-width: 150px;
        color: #4b5563;
        font-size: 12px;
        overflow-wrap: anywhere;
    }

    .job-contact-link {
        color: #2563eb;
        text-decoration: none;
    }

    .job-contact-link:hover {
        text-decoration: underline;
    }

    .job-contact-link i,
    .job-contact-text i {
        margin-top: 3px;
        flex-shrink: 0;
        color: #9ca3af;
    }

    .job-empty-value {
        color: #9ca3af;
        font-size: 12px;
        font-style: italic;
    }

    /* PAGINATION */
    .job-table-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 13px 16px;
        border-top: 1px solid #e5e7eb;
        background: #fff;
    }

    .job-pagination-info {
        color: #6b7280;
        font-size: 12px;
    }

    .job-pagination {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: wrap;
    }

    .job-page-numbers {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .job-page-btn,
    .job-page-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-width: 34px;
        height: 34px;
        padding: 0 10px;
        border: 1px solid #e5e7eb;
        border-radius: 7px;
        background: #fff;
        color: #4b5563;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s ease;
    }

    .job-page-btn:hover:not(:disabled),
    .job-page-number:hover {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #2563eb;
    }

    .job-page-number.active {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }

    .job-page-btn:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    /* EMPTY STATE */
    .job-empty-state {
        padding: 55px 20px;
        text-align: center;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .job-empty-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 65px;
        height: 65px;
        margin: 0 auto 15px;
        border-radius: 50%;
        background: #eff6ff;
        color: #2563eb;
        font-size: 25px;
    }

    .job-empty-state h3 {
        margin: 0 0 8px;
        color: #111827;
        font-size: 17px;
        font-weight: 700;
    }

    .job-empty-state p {
        margin: 0 auto 20px;
        max-width: 400px;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.6;
    }

    /* RESPONSIVE */
    @media (max-width:768px) {
        .job-table-footer {
            align-items: flex-start;
            flex-direction: column;
        }

        .job-pagination {
            width: 100%;
            justify-content: flex-start;
        }

        .job-pagination-info {
            font-size: 11px;
        }

        .job-posting-table {
            min-width: 1150px;
        }
    }

    @media (max-width:480px) {
        .job-pagination {
            gap: 4px;
        }

        .job-page-btn {
            padding: 0 8px;
            font-size: 11px;
        }

        .job-page-number {
            min-width: 30px;
            height: 32px;
        }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tableContainer = document.getElementById('jobPostingTable');
        const tableBody = document.getElementById('jobPostingTableBody');

        if (!tableContainer || !tableBody) {
            return;
        }

        const rows = Array.from(
            tableBody.querySelectorAll('.job-posting-row')
        );

        const rowsPerPage = 5;
        let currentPage = 1;

        const totalPages = Math.ceil(rows.length / rowsPerPage);

        const paginationInfo = document.getElementById('jobPaginationInfo');
        const pageNumbers = document.getElementById('jobPageNumbers');
        const previousButton = document.getElementById('jobPrevPage');
        const nextButton = document.getElementById('jobNextPage');

        function renderTable() {
            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex = Math.min(startIndex + rowsPerPage, rows.length);

            rows.forEach(function (row, index) {
                row.style.display =
                    index >= startIndex && index < endIndex ? '' : 'none';
            });

            paginationInfo.textContent =
                'Showing ' + (rows.length ? startIndex + 1 : 0) +
                ' to ' + endIndex +
                ' of ' + rows.length + ' entries';

            previousButton.disabled = currentPage === 1;
            nextButton.disabled = currentPage === totalPages || totalPages === 0;

            pageNumbers.innerHTML = '';

            for (let page = 1; page <= totalPages; page++) {
                const button = document.createElement('button');

                button.type = 'button';
                button.className = 'job-page-number';
                button.textContent = page;
                button.setAttribute('aria-label', 'Go to page ' + page);

                if (page === currentPage) {
                    button.classList.add('active');
                    button.setAttribute('aria-current', 'page');
                }

                button.addEventListener('click', function () {
                    currentPage = page;
                    renderTable();
                });

                pageNumbers.appendChild(button);
            }
        }

        previousButton.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        });

        nextButton.addEventListener('click', function () {
            if (currentPage < totalPages) {
                currentPage++;
                renderTable();
            }
        });

        // Desktop mouse drag-to-scroll.
        // Touch devices keep their native swipe behavior.
        let isDragging = false;
        let startX = 0;
        let scrollStart = 0;
        let moved = false;

        tableContainer.addEventListener('pointerdown', function (event) {
            if (event.pointerType !== 'mouse' || event.button !== 0) {
                return;
            }

            isDragging = true;
            moved = false;
            startX = event.clientX;
            scrollStart = tableContainer.scrollLeft;

            tableContainer.classList.add('is-dragging');
            tableContainer.setPointerCapture(event.pointerId);
        });

        tableContainer.addEventListener('pointermove', function (event) {
            if (!isDragging) {
                return;
            }

            const distance = event.clientX - startX;

            if (Math.abs(distance) > 3) {
                moved = true;
            }

            if (moved) {
                event.preventDefault();
                tableContainer.scrollLeft = scrollStart - distance;
            }
        });

        function stopDragging() {
            isDragging = false;
            tableContainer.classList.remove('is-dragging');
        }

        tableContainer.addEventListener('pointerup', stopDragging);
        tableContainer.addEventListener('pointercancel', stopDragging);
        tableContainer.addEventListener('lostpointercapture', stopDragging);

        // Prevent accidental link clicks after dragging the table.
        tableContainer.addEventListener('click', function (event) {
            if (moved) {
                event.preventDefault();
                event.stopPropagation();
                moved = false;
            }
        }, true);

        renderTable();
    });
</script>
<script>
    document.querySelectorAll('.modal').forEach(modal => {
        document.body.appendChild(modal);
    });
</script>
<style>
    .modal {
        z-index: 1055 !important;
    }

    .modal-backdrop {
        z-index: 1050 !important;
    }
</style>