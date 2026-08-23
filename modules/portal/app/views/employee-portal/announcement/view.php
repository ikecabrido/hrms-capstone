<div class="employee-dashboard">

    <section class="dashboard-welcome" id="announcementWelcome">

        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                EMPLOYEE ANNOUNCEMENT
            </span>

            <h1 class="welcome-title">
                Announcement
            </h1>

            <p class="welcome-description">
                View the details and information provided in this announcement.
            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration">
            <i class="fas fa-bullhorn"></i>
        </div>

    </section>


<section class="dashboard-section" style="
    width:100%;
    box-sizing:border-box;
    padding:24px;
">

    <!-- BACK -->
    <div style="
        margin-bottom:20px;
    ">

        <a href="index.php?url=announcement" style="
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
            Back to Announcements
        </a>

    </div>


    <!-- ANNOUNCEMENT CARD -->
    <div style="
        width:100%;
        max-width:1100px;
        margin:0 auto;
        box-sizing:border-box;
        background:#fff;
        border:1px solid #e2e8f0;
        border-radius:16px;
        overflow:hidden;
        box-shadow:0 4px 16px rgba(15,23,42,.05);
    ">


        <!-- TOP ACCENT -->
        <div style="
            height:5px;
            width:100%;
            background:#2563eb;
        "></div>


        <!-- HEADER -->
        <div style="
            padding:30px 32px;
            border-bottom:1px solid #e5e7eb;
            background:linear-gradient(to bottom,#f8fafc,#fff);
        ">

            <div style="
                display:flex;
                align-items:flex-start;
                gap:20px;
            ">

                <!-- ICON -->
                <div style="
                    width:58px;
                    height:58px;
                    min-width:58px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border-radius:15px;
                    background:#eff6ff;
                    color:#2563eb;
                    font-size:22px;
                    box-shadow:0 2px 6px rgba(37,99,235,.08);
                ">
                    <i class="fas fa-bullhorn"></i>
                </div>


                <!-- HEADER CONTENT -->
                <div style="
                    flex:1;
                    min-width:0;
                ">

                    <div style="
                        display:flex;
                        align-items:center;
                        gap:8px;
                        margin-bottom:8px;
                    ">

                        <span style="
                            display:inline-flex;
                            align-items:center;
                            gap:5px;
                            padding:5px 9px;
                            border-radius:20px;
                            background:#eff6ff;
                            color:#2563eb;
                            font-size:9px;
                            font-weight:700;
                            text-transform:uppercase;
                            letter-spacing:.3px;
                        ">
                            <i class="fas fa-bullhorn"></i>
                            Announcement
                        </span>

                    </div>


                    <h2 style="
                        margin:0;
                        color:#111827;
                        font-size:26px;
                        font-weight:700;
                        line-height:1.3;
                        letter-spacing:-.3px;
                    ">
                        <?= htmlspecialchars(
                            $announcement['title'] ?? 'Announcement'
                        ) ?>
                    </h2>


                    <!-- META -->
                    <div style="
                        display:flex;
                        align-items:center;
                        flex-wrap:wrap;
                        gap:18px;
                        margin-top:13px;
                        color:#64748b;
                        font-size:11px;
                    ">

                        <span style="
                            display:inline-flex;
                            align-items:center;
                            gap:6px;
                        ">
                            <i class="far fa-clock" style="
                                color:#2563eb;
                            "></i>

                            <?= !empty($announcement['created_at'])
                                ? date(
                                    'M d, Y h:i A',
                                    strtotime($announcement['created_at'])
                                )
                                : '-' ?>
                        </span>


                        <span style="
                            display:inline-flex;
                            align-items:center;
                            gap:6px;
                        ">
                            <i class="fas fa-users" style="
                                color:#2563eb;
                            "></i>

                            <?= htmlspecialchars(
                                $announcement['target_audience']
                                ?? 'All Employees'
                            ) ?>
                        </span>


                        <span style="
                            display:inline-flex;
                            align-items:center;
                            gap:6px;
                        ">
                            <i class="fas fa-user" style="
                                color:#2563eb;
                            "></i>

                            <?= htmlspecialchars(
                                $announcement['created_by'] ?? 'HR'
                            ) ?>
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- CONTENT AREA -->
        <div style="
            padding:32px;
        ">

            <!-- CONTENT LABEL -->
            <div style="
                display:flex;
                align-items:center;
                gap:8px;
                margin-bottom:15px;
                color:#111827;
                font-size:12px;
                font-weight:700;
            ">
                <span style="
                    width:4px;
                    height:18px;
                    border-radius:3px;
                    background:#2563eb;
                "></span>

                Announcement Details
            </div>


            <!-- CONTENT BOX -->
            <div style="
                padding:24px 26px;
                border:1px solid #e5e7eb;
                border-radius:12px;
                background:#f8fafc;
            ">

                <div style="
                    color:#374151;
                    font-size:14px;
                    line-height:1.9;
                    white-space:normal;
                    overflow-wrap:anywhere;
                ">
                    <?= nl2br(
                        htmlspecialchars(
                            $announcement['content'] ?? ''
                        )
                    ) ?>
                </div>

            </div>

        </div>


        <!-- FOOTER -->
        <div style="
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:15px;
            padding:18px 32px;
            border-top:1px solid #e5e7eb;
            background:#f8fafc;
            flex-wrap:wrap;
        ">

            <!-- POSTED INFO -->
            <div style="
                display:flex;
                align-items:center;
                gap:10px;
            ">

                <div style="
                    width:34px;
                    height:34px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border-radius:9px;
                    background:#fff;
                    border:1px solid #e5e7eb;
                    color:#64748b;
                    font-size:12px;
                ">
                    <i class="fas fa-user"></i>
                </div>


                <div>

                    <div style="
                        color:#9ca3af;
                        font-size:9px;
                        margin-bottom:2px;
                    ">
                        POSTED BY
                    </div>

                    <strong style="
                        color:#374151;
                        font-size:11px;
                        font-weight:600;
                    ">
                        <?= htmlspecialchars(
                            $announcement['created_by'] ?? 'HR'
                        ) ?>
                    </strong>

                </div>

            </div>


            <!-- BACK BUTTON -->
            <a href="index.php?url=announcement" style="
                display:inline-flex;
                align-items:center;
                gap:7px;
                padding:9px 15px;
                border:1px solid #d1d5db;
                border-radius:9px;
                background:#fff;
                color:#374151;
                text-decoration:none;
                font-size:11px;
                font-weight:600;
                transition:.2s ease;
            "
            onmouseover="
                this.style.background='#f1f5f9';
                this.style.borderColor='#94a3b8';
            "
            onmouseout="
                this.style.background='#fff';
                this.style.borderColor='#d1d5db';
            ">
                <i class="fas fa-arrow-left"></i>
                All Announcements
            </a>

        </div>

    </div>

</section>

</div>