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
                                                    <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank"
                                                        rel="noopener noreferrer" title="View File" style="
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:30px;
            height:30px;
            border-radius:8px;
            background:#eff6ff;
            color:#2563eb;
            text-decoration:none;
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