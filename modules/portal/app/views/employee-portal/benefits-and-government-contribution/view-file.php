<?php foreach ($records as $record): ?>

    <?php if (!empty($record['file_path'])): ?>

        <div
            class="modal fade"
            id="viewBenefitFile<?= (int) $record['benefit_id'] ?>"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-xl">

                <div class="modal-content" style="
                    border:0;
                    border-radius:12px;
                    overflow:hidden;
                ">

                    <div class="modal-header" style="
                        padding:14px 18px;
                        border-bottom:1px solid #e5e7eb;
                    ">

                        <div>
                            <h5 style="
                                margin:0;
                                font-size:14px;
                                font-weight:700;
                                color:#111827;
                            ">
                                <?= htmlspecialchars($record['file_name'] ?: 'Document') ?>
                            </h5>

                            <div style="
                                margin-top:3px;
                                font-size:10px;
                                color:#6b7280;
                            ">
                                <?= htmlspecialchars($record['record_type'] ?? '-') ?>
                                ·
                                <?= htmlspecialchars($record['period'] ?? '-') ?>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                        ></button>

                    </div>

                    <div class="modal-body" style="
                        padding:0;
                        height:70vh;
                        background:#f8fafc;
                    ">

                        <?php
                        $extension = strtolower(
                            pathinfo($record['file_path'], PATHINFO_EXTENSION)
                        );

                        $fileUrl = '/' . ltrim($record['file_path'], '/');
                        ?>

                        <?php if ($extension === 'pdf'): ?>

                            <iframe
                                src="<?= htmlspecialchars($fileUrl) ?>"
                                style="
                                    width:100%;
                                    height:100%;
                                    border:0;
                                "
                            ></iframe>

                        <?php elseif (in_array($extension, ['jpg', 'jpeg', 'png'], true)): ?>

                            <div style="
                                width:100%;
                                height:100%;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                padding:20px;
                            ">
                                <img
                                    src="<?= htmlspecialchars($fileUrl) ?>"
                                    alt="Document"
                                    style="
                                        max-width:100%;
                                        max-height:100%;
                                        object-fit:contain;
                                    "
                                >
                            </div>

                        <?php else: ?>

                            <div style="
                                height:100%;
                                display:flex;
                                flex-direction:column;
                                align-items:center;
                                justify-content:center;
                                color:#6b7280;
                                font-size:12px;
                                gap:10px;
                            ">
                                <i
                                    class="fas fa-file"
                                    style="
                                        font-size:35px;
                                        color:#2563eb;
                                    "
                                ></i>

                                <div>
                                    Preview is not available for this file type.
                                </div>

                                <a
                                    href="<?= htmlspecialchars($fileUrl) ?>"
                                    download="<?= htmlspecialchars($record['file_name'] ?: 'document') ?>"
                                    style="
                                        display:inline-flex;
                                        align-items:center;
                                        gap:6px;
                                        padding:7px 12px;
                                        border-radius:7px;
                                        background:#2563eb;
                                        color:#fff;
                                        text-decoration:none;
                                        font-size:11px;
                                        font-weight:600;
                                    "
                                >
                                    <i class="fas fa-download"></i>
                                    Download File
                                </a>
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="modal-footer" style="
                        padding:10px 18px;
                        border-top:1px solid #e5e7eb;
                    ">

                        <button
                            type="button"
                            data-bs-dismiss="modal"
                            style="
                                padding:7px 12px;
                                border:1px solid #d1d5db;
                                border-radius:7px;
                                background:#fff;
                                color:#374151;
                                font-size:11px;
                                font-weight:600;
                            "
                        >
                            Close
                        </button>

                    </div>

                </div>
            </div>
        </div>

    <?php endif; ?>

<?php endforeach; ?>