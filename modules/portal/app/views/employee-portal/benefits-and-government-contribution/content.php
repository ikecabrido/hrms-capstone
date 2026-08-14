<div class="employee-dashboard">

    <section class="dashboard-welcome" id="benefitsWelcome">
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                BENEFITS & GOVERNMENT CONTRIBUTIONS
            </span>

            <h1 class="welcome-title">
                Benefits & Contributions
            </h1>

            <p class="welcome-description">
                View your government contributions and benefits records, including SSS,
                PhilHealth, Pag-IBIG, withholding tax, and BIR Form 2316 documents.
            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration">
            <i class="fas fa-hand-holding-dollar"></i>
        </div>
    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section class="dashboard-section" style="width:100%;box-sizing:border-box;">

        <!-- HEADER -->
        <div style="
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:16px;
    padding-bottom:12px;
    border-bottom:1px solid #e5e7eb;
    flex-wrap:wrap;
">

            <!-- TITLE -->
            <div style="
        display:flex;
        align-items:center;
        gap:11px;
    ">

                <div style="
            width:40px;
            height:40px;
            min-width:40px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:10px;
            background:#eff6ff;
            color:#2563eb;
            font-size:16px;
        ">
                    <i class="fas fa-building-columns"></i>
                </div>

                <div>

                    <h3 style="
                margin:0;
                color:#111827;
                font-size:24px;
                font-weight:700;
            ">
                        Benefits & Government Contributions
                    </h3>

                    <p style="
                margin:3px 0 0;
                color:#6b7280;
                font-size:12px;
            ">
                        View your government contribution records and documents.
                    </p>

                </div>

            </div>


            <!-- RIGHT SIDE -->
            <div style="
        display:flex;
        align-items:center;
        gap:8px;
        flex-wrap:wrap;
    ">

                <!-- RECORD COUNT -->
                <span style="
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:7px 11px;
            border-radius:20px;
            background:#f8fafc;
            border:1px solid #e5e7eb;
            color:#374151;
            font-size:11px;
            font-weight:600;
            white-space:nowrap;
        ">
                    <i class="fas fa-file-lines" style="color:#2563eb;"></i>

                    <?= count($benefits ?? []) ?> Records
                </span>


                <!-- SUBMIT DOCUMENT -->
                <button type="button" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#submitBenefitModal"
                    style="
                display:inline-flex;
                align-items:center;
                gap:6px;
                padding:8px 12px;
                border:0;
                border-radius:8px;
                background:#2563eb;
                color:#fff;
                font-size:11px;
                font-weight:600;
                white-space:nowrap;
            ">
                    <i class="fas fa-plus"></i>
                    Submit Document
                </button>

            </div>

        </div>


        <?php if (!empty($benefits)): ?>

            <?php
            $folders = [];

            foreach ($benefits as $benefit) {
                $type = $benefit['record_type'] ?? 'Other';
                $folders[$type][] = $benefit;
            }
            ?>

            <!-- FOLDERS -->
            <div style="
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(210px,1fr));
            gap:14px;
        ">

                <?php foreach ($folders as $type => $records): ?>

                    <?php $folderId = 'benefitFolder' . md5($type); ?>

                    <div data-bs-toggle="modal" data-bs-target="#<?= $folderId ?>" style="
            padding:16px;
            border:1px solid #e5e7eb;
            border-radius:12px;
            background:#fff;
            cursor:pointer;
            transition:.2s ease;
        " onmouseover="
            this.style.background='#f8fafc';
            this.style.borderColor='#bfdbfe';
        " onmouseout="
            this.style.background='#fff';
            this.style.borderColor='#e5e7eb';
        ">

                        <div style="
            width:48px;
            height:48px;
            display:flex;
            align-items:center;
            justify-content:center;
            margin-bottom:13px;
            border-radius:11px;
            background:#eff6ff;
            color:#2563eb;
            font-size:22px;
        ">
                            <i class="fas fa-folder"></i>
                        </div>

                        <div style="
            color:#111827;
            font-size:13px;
            font-weight:700;
            margin-bottom:4px;
        ">
                            <?= htmlspecialchars($type) ?>
                        </div>

                        <div style="
            color:#6b7280;
            font-size:11px;
        ">
                            <?= count($records) ?>
                            <?= count($records) === 1 ? 'document' : 'documents' ?>
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


        <?php else: ?>

            <!-- EMPTY STATE -->
            <div style="
            width:100%;
            padding:50px 20px;
            text-align:center;
            border:1px solid #e5e7eb;
            border-radius:12px;
            background:#fff;
        ">

                <div style="
                width:55px;
                height:55px;
                margin:0 auto 14px;
                display:flex;
                align-items:center;
                justify-content:center;
                border-radius:14px;
                background:#eff6ff;
                color:#93c5fd;
                font-size:23px;
            ">
                    <i class="fas fa-folder-open"></i>
                </div>

                <h5 style="
                margin:0 0 5px;
                color:#374151;
                font-size:15px;
                font-weight:700;
            ">
                    No Documents
                </h5>

                <p style="
                margin:0;
                color:#9ca3af;
                font-size:12px;
            ">
                    Your benefits and government contribution documents will appear here.
                </p>

            </div>

        <?php endif; ?>

    </section>

</div>