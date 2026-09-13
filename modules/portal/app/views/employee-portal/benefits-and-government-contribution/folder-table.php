<?php if (!empty($folders)): ?>
    <?php foreach ($folders as $type => $records): ?>

        <?php $folderId = 'benefitFolder' . md5($type); ?>

        <div class="modal fade" id="<?= $folderId ?>" tabindex="-1" aria-labelledby="<?= $folderId ?>Label" aria-hidden="true">

            <div class="modal-dialog modal-dialog-centered modal-lg">

                <div class="modal-content" style="
                border:0;
                border-radius:14px;
                overflow:hidden;
                box-shadow:0 10px 30px rgba(0,0,0,.12);
            ">

                    <!-- HEADER -->
                    <div class="modal-header" style="
                    padding:18px 20px;
                    border-bottom:1px solid #e5e7eb;
                    background:#fff;
                ">

                        <div style="
                        display:flex;
                        align-items:center;
                        gap:11px;
                    ">

                            <div style="
                            width:40px;
                            height:40px;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            border-radius:10px;
                            background:#eff6ff;
                            color:#2563eb;
                        ">
                                <i class="fas fa-folder"></i>
                            </div>

                            <div>

                                <h5 id="<?= $folderId ?>Label" style="
                                    margin:0;
                                    color:#111827;
                                    font-size:16px;
                                    font-weight:700;
                                ">
                                    <?= htmlspecialchars($type) ?>
                                </h5>

                                <p style="
                                margin:3px 0 0;
                                color:#6b7280;
                                font-size:11px;
                            ">
                                    <?= count($records) ?>
                                    <?= count($records) === 1 ? 'document' : 'documents' ?>
                                </p>

                            </div>

                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                    </div>


                    <!-- BODY -->
                    <div class="modal-body" style="
                    padding:20px;
                    background:#fff;
                ">

                        <div style="
                        width:100%;
                        overflow-x:auto;
                        border:1px solid #e5e7eb;
                        border-radius:10px;
                    ">

                            <table style="
                            width:100%;
                            border-collapse:collapse;
                            font-size:12px;
                        ">

                                <thead>

                                    <tr style="
                                    background:#f8fafc;
                                    border-bottom:1px solid #e5e7eb;
                                ">

                                        <th style="
                                        padding:12px 14px;
                                        text-align:left;
                                        color:#64748b;
                                        font-size:10px;
                                        font-weight:700;
                                    ">
                                            File
                                        </th>

                                        <th style="
                                        padding:12px 14px;
                                        text-align:left;
                                        color:#64748b;
                                        font-size:10px;
                                        font-weight:700;
                                    ">
                                            Period
                                        </th>

                                        <th style="
                                        padding:12px 14px;
                                        text-align:left;
                                        color:#64748b;
                                        font-size:10px;
                                        font-weight:700;
                                    ">
                                            Description
                                        </th>

                                        <th style="
                                        padding:12px 14px;
                                        text-align:left;
                                        color:#64748b;
                                        font-size:10px;
                                        font-weight:700;
                                    ">
                                            Uploaded
                                        </th>

                                        <th style="
                                        padding:12px 14px;
                                        text-align:center;
                                        color:#64748b;
                                        font-size:10px;
                                        font-weight:700;
                                    ">
                                            Action
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($records as $record): ?>

                                        <tr style="
                                        border-bottom:1px solid #f1f5f9;
                                    ">

                                            <!-- FILE -->
                                            <td style="
                                            padding:13px 14px;
                                            color:#111827;
                                            font-weight:600;
                                        ">

                                                <div style="
                                                display:flex;
                                                align-items:center;
                                                gap:8px;
                                            ">

                                                    <i class="fas fa-file" style="color:#2563eb;"></i>

                                                    <span>
                                                        <?= htmlspecialchars(
                                                            $record['file_name']
                                                            ?: 'No file'
                                                        ) ?>
                                                    </span>

                                                </div>

                                            </td>

                                            <!-- PERIOD -->
                                            <td style="
                                                padding:13px 14px;
                                                color:#374151;
                                                white-space:nowrap;">
                                                <?php if (!empty($record['period'])): ?>
                                                    <?= date('F Y', strtotime($record['period'] . '-01')) ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>

                                            <!-- DESCRIPTION -->
                                            <td style="
                                            padding:13px 14px;
                                            color:#374151;
                                        ">
                                                <?= htmlspecialchars(
                                                    $record['description'] ?? '-'
                                                ) ?>
                                            </td>


                                            <!-- DATE -->
                                            <td style="
                                            padding:13px 14px;
                                            color:#6b7280;
                                            white-space:nowrap;
                                        ">
                                                <?= !empty($record['uploaded_at'])
                                                    ? date(
                                                        'M d, Y',
                                                        strtotime($record['uploaded_at'])
                                                    )
                                                    : '-' ?>
                                            </td>


                                            <!-- ACTION -->
                                            <td style="
    padding:13px 14px;
    text-align:center;
    white-space:nowrap;
">

                                                <?php if (!empty($record['file_path'])): ?>

                                                    <?php
                                                    $base = '/hrms-capstone/modules/portal/public';
                                                    $fileUrl = $base . '/' . ltrim($record['file_path'], '/');
                                                    ?>

                                                    <!-- VIEW -->
                                                    <a href="javascript:void(0);"
                                                        onclick="viewFile('<?= htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8') ?>')"
                                                        title="View File" style="
       display:inline-flex;
       align-items:center;
       justify-content:center;
       width:30px;
       height:30px;
       border-radius:8px;
       background:#eff6ff;
       color:#2563eb;
       text-decoration:none;
       cursor:pointer;
   ">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    <!-- DOWNLOAD -->
                                                    <a href="<?= htmlspecialchars($fileUrl) ?>"
                                                        download="<?= htmlspecialchars($record['file_name'] ?: 'document') ?>"
                                                        title="Download File" style="
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:30px;
            height:30px;
            border-radius:8px;
            background:#f0fdf4;
            color:#16a34a;
            text-decoration:none;
        ">
                                                        <i class="fas fa-download"></i>
                                                    </a>

                                                <?php else: ?>

                                                    <span title="No file attached" style="
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:30px;
            height:30px;
            border-radius:8px;
            background:#f3f4f6;
            color:#9ca3af;
        ">
                                                        <i class="fas fa-file-circle-xmark"></i>
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>


                    <!-- FOOTER -->
                    <div class="modal-footer" style="
                    padding:13px 20px;
                    border-top:1px solid #e5e7eb;
                    background:#f8fafc;
                ">

                        <button type="button" data-bs-dismiss="modal" style="
                            padding:8px 14px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                            background:#fff;
                            color:#374151;
                            font-size:11px;
                            font-weight:600;
                        ">
                            Close
                        </button>

                    </div>

                </div>

            </div>

        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Bootstrap 5 -->

<div class="modal fade" id="fileViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content file-view-modal">

            <!-- Close -->
            <button type="button" class="file-close-button" data-bs-dismiss="modal" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>

            <!-- Flexbox centers the image horizontally and vertically -->
            <div class="file-viewer-container">

                <!-- Loading -->
                <div id="fileLoading" class="file-loading">
                    <div class="file-spinner"></div>
                </div>

                <!-- Responsive image -->
                <img id="imageViewer" src="" alt="File Preview">

            </div>

        </div>
    </div>
</div>

<style>
    /* Backdrop */
    #fileViewModal+.modal-backdrop {
        background: rgba(15, 23, 42, 0.72);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    /* Modal */
    .file-view-modal {
        position: relative;
        width: 100%;
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
    }

    /* Close button */
    .file-close-button {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 10;

        width: 38px;
        height: 38px;
        padding: 0;

        display: flex;
        align-items: center;
        justify-content: center;

        border: 0;
        border-radius: 50%;

        background: rgba(255, 255, 255, 0.92);
        color: #374151;

        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);

        cursor: pointer;
        transition: 0.2s ease;
    }

    .file-close-button:hover {
        background: #fff;
        color: #111827;
        transform: scale(1.05);
    }

    /* Flexbox provides perfect horizontal + vertical centering */
    .file-viewer-container {
        position: relative;

        width: 100%;
        height: min(80vh, 850px);

        display: flex;
        align-items: center;
        justify-content: center;

        padding: 20px;

        background: #fff;
        overflow: hidden;
    }

    /* Image stays inside the modal without distortion */
    #imageViewer {
        display: none;

        width: auto;
        height: auto;

        max-width: 100%;
        max-height: 100%;

        object-fit: contain;

        border-radius: 8px;

        opacity: 0;
        transition: opacity 0.25s ease;
    }

    #imageViewer.loaded {
        opacity: 1;
    }

    /* Loading overlay */
    .file-loading {
        position: absolute;
        inset: 0;
        z-index: 5;

        display: none;
        align-items: center;
        justify-content: center;

        background: #fff;
    }

    /* Spinner */
    .file-spinner {
        width: 36px;
        height: 36px;

        border: 3px solid #e5e7eb;
        border-top-color: #2563eb;

        border-radius: 50%;

        animation: fileSpinner 0.7s linear infinite;
    }

    @keyframes fileSpinner {
        to {
            transform: rotate(360deg);
        }
    }

    /* Mobile */
    @media (max-width: 768px) {
        #fileViewModal .modal-dialog {
            margin: 10px;
        }

        .file-view-modal {
            border-radius: 12px;
        }

        .file-viewer-container {
            height: 80vh;
            padding: 15px;
        }

        #imageViewer {
            max-width: 100%;
            max-height: 100%;
        }

        .file-close-button {
            top: 8px;
            right: 8px;
            width: 34px;
            height: 34px;
        }
    }

    /* Small phones */
    @media (max-width: 480px) {
        #fileViewModal .modal-dialog {
            margin: 5px;
        }

        .file-viewer-container {
            height: 85vh;
            padding: 10px;
        }
    }
</style>

<script>
    function viewFile(fileUrl) {
        const modalElement = document.getElementById('fileViewModal');
        const image = document.getElementById('imageViewer');
        const loading = document.getElementById('fileLoading');

        // Reset previous image
        image.onload = null;
        image.onerror = null;
        image.src = '';
        image.classList.remove('loaded');
        image.style.display = 'none';

        // Show loading
        loading.style.display = 'flex';

        // Load new image
        image.onload = function () {
            loading.style.display = 'none';
            image.style.display = 'block';

            requestAnimationFrame(() => {
                image.classList.add('loaded');
            });
        };

        image.onerror = function () {
            loading.style.display = 'none';
            image.style.display = 'none';
        };

        image.src = fileUrl;

        // Show Bootstrap modal
        bootstrap.Modal
            .getOrCreateInstance(modalElement)
            .show();

        // Cleanup after closing
        modalElement.addEventListener(
            'hidden.bs.modal',
            function cleanup() {
                image.onload = null;
                image.onerror = null;
                image.src = '';
                image.classList.remove('loaded');
                image.style.display = 'none';
                loading.style.display = 'none';
            },
            { once: true }
        );
    }
</script>