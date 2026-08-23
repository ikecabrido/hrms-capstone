<div class="employee-dashboard">

    <section class="dashboard-welcome" id="announcementWelcome">
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                EMPLOYEE ANNOUNCEMENTS
            </span>

            <h1 class="welcome-title">
                Announcements
            </h1>

            <p class="welcome-description">
                Stay informed with the latest announcements, updates, and important notices from HR.
            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration">
            <i class="fas fa-bullhorn"></i>
        </div>
    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section class="dashboard-section" style="
    width:100%;
    box-sizing:border-box;
">

        <!-- HEADER -->
        <div style="
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:15px;
        margin-bottom:18px;
        padding-bottom:14px;
        border-bottom:1px solid #e5e7eb;
        flex-wrap:wrap;
    ">

            <div style="
            display:flex;
            align-items:center;
            gap:12px;
        ">

                <div style="
                width:42px;
                height:42px;
                min-width:42px;
                display:flex;
                align-items:center;
                justify-content:center;
                border-radius:11px;
                background:#eff6ff;
                color:#2563eb;
                font-size:17px;
            ">
                    <i class="fas fa-bullhorn"></i>
                </div>

                <div>
                    <h3 style="
                    margin:0;
                    color:#111827;
                    font-size:20px;
                    font-weight:700;
                ">
                        Announcements
                    </h3>

                    <p style="
                    margin:3px 0 0;
                    color:#6b7280;
                    font-size:11px;
                ">
                        Important updates and notices from HR.
                    </p>
                </div>

            </div>

            <span style="
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:7px 11px;
            border-radius:20px;
            background:#f8fafc;
            border:1px solid #e5e7eb;
            color:#374151;
            font-size:10px;
            font-weight:600;
            white-space:nowrap;
        ">
                <i class="fas fa-bullhorn" style="color:#2563eb;"></i>
                <?= count($announcements ?? []) ?> Announcements
            </span>

        </div>
    <!-- BACK -->
    <div style="
        margin-bottom:20px;
    ">

        <a href="index.php?url=employee-dashboard" style="
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:9px 14px;
            border:1px solid #e5e7eb;
            border-radius:9px;
            background:#fff;
            color:#374151;
            text-decoration:none;
            font-size:12px;
            font-weight:600;
            transition:.2s ease;
        "
        onmouseover="
            this.style.background='#f8fafc';
            this.style.borderColor='#cbd5e1';
        "
        onmouseout="
            this.style.background='#fff';
            this.style.borderColor='#e5e7eb';
        ">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

    </div>

        <?php if (!empty($announcements)): ?>

            <div style="
            display:flex;
            flex-direction:column;
            gap:12px;
        ">

                <?php foreach ($announcements as $announcement): ?>

                    <?php
                    $createdAt = strtotime($announcement['created_at']);
                    $isNew = $createdAt >= strtotime('-7 days');
                    ?>
                    <a href="index.php?url=announcement-view&id=<?= (int) $announcement['eer_announcements_id'] ?>" style="text-decoration: none;">
                        <article style="
                    position:relative;
                    display:flex;
                    gap:15px;
                    padding:17px;
                    border:1px solid #e5e7eb;
                    border-radius:13px;
                    background:#fff;
                    transition:all .2s ease;
                    overflow:hidden;
                " onmouseover="
                    this.style.borderColor='#bfdbfe';
                    this.style.boxShadow='0 4px 14px rgba(37,99,235,.07)';
                    this.style.transform='translateY(-1px)';
                " onmouseout="
                    this.style.borderColor='#e5e7eb';
                    this.style.boxShadow='none';
                    this.style.transform='translateY(0)';
                ">

                            <!-- LEFT ACCENT -->
                            <div style="
                        position:absolute;
                        left:0;
                        top:0;
                        bottom:0;
                        width:3px;
                        background:#2563eb;
                    "></div>


                            <!-- ICON -->
                            <div style="
                        width:44px;
                        height:44px;
                        min-width:44px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        border-radius:11px;
                        background:#eff6ff;
                        color:#2563eb;
                        font-size:16px;
                    ">
                                <i class="fas fa-bullhorn"></i>
                            </div>


                            <!-- CONTENT -->
                            <div style="
                        flex:1;
                        min-width:0;
                    ">

                                <!-- TITLE ROW -->
                                <div style="
                            display:flex;
                            align-items:center;
                            gap:8px;
                            flex-wrap:wrap;
                            margin-bottom:5px;
                        ">

                                    <h4 style="
                                margin:0;
                                color:#111827;
                                font-size:14px;
                                font-weight:700;
                                line-height:1.4;
                            ">
                                        <?= htmlspecialchars($announcement['title']) ?>
                                    </h4>

                                    <?php if ($isNew): ?>

                                        <span style="
                                    display:inline-flex;
                                    align-items:center;
                                    padding:3px 7px;
                                    border-radius:20px;
                                    background:#eff6ff;
                                    color:#2563eb;
                                    font-size:8px;
                                    font-weight:700;
                                    letter-spacing:.3px;
                                ">
                                            NEW
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <!-- MESSAGE -->
                                <p style="
                            margin:0 0 10px;
                            color:#4b5563;
                            font-size:11px;
                            line-height:1.6;
                        ">
                                    <?= nl2br(htmlspecialchars($announcement['content'])) ?>
                                </p>


                                <!-- META -->
                                <div style="
                            display:flex;
                            align-items:center;
                            gap:12px;
                            flex-wrap:wrap;
                            color:#9ca3af;
                            font-size:9px;
                        ">

                                    <span style="
                                display:inline-flex;
                                align-items:center;
                                gap:5px;
                            ">
                                        <i class="far fa-clock"></i>
                                        <?= date('M d, Y · h:i A', $createdAt) ?>
                                    </span>

                                    <span style="
                                display:inline-flex;
                                align-items:center;
                                gap:5px;
                            ">
                                        <i class="fas fa-users"></i>
                                        <?= htmlspecialchars(ucwords($announcement['target_audience'])) ?>
                                    </span>

                                    <?php if (!empty($announcement['created_by'])): ?>

                                        <span style="
                                    display:inline-flex;
                                    align-items:center;
                                    gap:5px;
                                ">
                                            <i class="fas fa-user"></i>
                                            <?= htmlspecialchars($announcement['created_by']) ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </article>
                    </a>
                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <!-- EMPTY STATE -->
            <div style="
            width:100%;
            box-sizing:border-box;
            padding:55px 20px;
            text-align:center;
            border:1px dashed #d1d5db;
            border-radius:13px;
            background:#fafafa;
        ">

                <div style="
                width:58px;
                height:58px;
                margin:0 auto 15px;
                display:flex;
                align-items:center;
                justify-content:center;
                border-radius:15px;
                background:#eff6ff;
                color:#93c5fd;
                font-size:24px;
            ">
                    <i class="fas fa-bullhorn"></i>
                </div>

                <h5 style="
                margin:0 0 6px;
                color:#374151;
                font-size:15px;
                font-weight:700;
            ">
                    No Announcements
                </h5>

                <p style="
                margin:0;
                color:#9ca3af;
                font-size:11px;
            ">
                    There are currently no announcements available.
                </p>

            </div>

        <?php endif; ?>

    </section>
</div>