<div class="employee-dashboard">

    <section class="dashboard-welcome" id="dashboardWelcome">

        <!-- Decorative animated background -->
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label" id="welcomeLabel">
                <i class="fas fa-circle"></i>
                EMPLOYEE PORTAL
            </span>

            <h1 id="welcomeTitle">
                Welcome back,
                <span>
                    <?= htmlspecialchars($employeeDashboard['first_name'] ?? 'Employee'); ?>
                </span>
            </h1>

            <p id="welcomeDescription">
                Manage your employee services, records, requests, and activities.
            </p>

            <div class="welcome-line"></div>

        </div>

        <!-- Decorative icon -->
        <div class="welcome-decoration" id="welcomeDecoration">
            <i class="fas fa-user-tie"></i>
        </div>

    </section>

    <?php require __DIR__ . '/../partials/notification.php'; ?>

    <section class="dashboard-section">

        <div class="dashboard-section-header">
            <div>
                <span>ANNOUNCEMENTS</span>
                <h2>Latest Announcements</h2>
            </div>

            <a href="index.php?url=announcement" style="
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:7px 11px;
            border:1px solid #e5e7eb;
            border-radius:8px;
            background:#fff;
            color:#374151;
            text-decoration:none;
            font-size:11px;
            font-weight:600;
        ">
                View All
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>


        <?php if (!empty($announcements)): ?>

            <div style="
            display:flex;
            flex-direction:column;
            gap:10px;
        ">

                <?php foreach (array_slice($announcements, 0, 5) as $announcement): ?>

                    <a href="index.php?url=announcement-view&id=<?= (int) $announcement['eer_announcements_id'] ?>" style="
                        display:flex;
                        align-items:center;
                        gap:13px;
                        width:100%;
                        padding:14px 15px;
                        box-sizing:border-box;
                        border:1px solid #e5e7eb;
                        border-radius:11px;
                        background:#fff;
                        text-decoration:none;
                        transition:.2s ease;
                    " onmouseover="
                        this.style.background='#f8fafc';
                        this.style.borderColor='#bfdbfe';
                    " onmouseout="
                        this.style.background='#fff';
                        this.style.borderColor='#e5e7eb';
                    ">

                        <!-- ICON -->
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
                        font-size:15px;
                    ">
                            <i class="fas fa-bullhorn"></i>
                        </div>


                        <!-- CONTENT -->
                        <div style="
                        flex:1;
                        min-width:0;
                    ">

                            <h3 style="
                            margin:0 0 5px;
                            color:#111827;
                            font-size:13px;
                            font-weight:700;
                            overflow:hidden;
                            text-overflow:ellipsis;
                            white-space:nowrap;
                        ">
                                <?= htmlspecialchars(
                                    $announcement['title'] ?? 'Announcement'
                                ) ?>
                            </h3>


                            <p style="
                            margin:0;
                            color:#6b7280;
                            font-size:11px;
                            line-height:1.5;
                            overflow:hidden;
                            text-overflow:ellipsis;
                            white-space:nowrap;
                        ">
                                <?= htmlspecialchars(
                                    $announcement['content'] ?? ''
                                ) ?>
                            </p>


                            <!-- META -->
                            <div style="
                            display:flex;
                            align-items:center;
                            gap:12px;
                            margin-top:6px;
                            color:#9ca3af;
                            font-size:9px;
                        ">

                                <span>
                                    <i class="far fa-clock"></i>

                                    <?= !empty($announcement['created_at'])
                                        ? date(
                                            'M d, Y h:i A',
                                            strtotime($announcement['created_at'])
                                        )
                                        : '-' ?>
                                </span>


                                <span>
                                    <i class="fas fa-users"></i>

                                    <?= htmlspecialchars(
                                        $announcement['target_audience'] ?? 'All Employees'
                                    ) ?>
                                </span>

                            </div>

                        </div>


                        <!-- ARROW -->
                        <i class="fas fa-chevron-right" style="
                        color:#9ca3af;
                        font-size:10px;
                    "></i>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <!-- EMPTY STATE -->
            <div style="
            width:100%;
            padding:35px 20px;
            text-align:center;
            border:1px solid #e5e7eb;
            border-radius:11px;
            background:#fff;
            box-sizing:border-box;
        ">

                <div style="
                width:48px;
                height:48px;
                margin:0 auto 12px;
                display:flex;
                align-items:center;
                justify-content:center;
                border-radius:12px;
                background:#eff6ff;
                color:#93c5fd;
                font-size:20px;
            ">
                    <i class="fas fa-bullhorn"></i>
                </div>

                <h3 style="
                margin:0 0 4px;
                color:#374151;
                font-size:13px;
                font-weight:700;
            ">
                    No Announcements
                </h3>

                <p style="
                margin:0;
                color:#9ca3af;
                font-size:10px;
            ">
                    There are no announcements available at this time.
                </p>

            </div>

        <?php endif; ?>

    </section>

    <section class="dashboard-section">

        <div class="dashboard-section-header">
            <div>
                <span>QUICK ACCESS</span>
                <h2>Employee Services</h2>
            </div>
        </div>


        <div class="quick-access-grid">

            <!-- Attendance -->
            <a href="index.php?url=attendance" class="dashboard-card">

                <div class="dashboard-card-icon attendance">
                    <i class="fas fa-clock"></i>
                </div>

                <div class="dashboard-card-content">
                    <h3>Attendance</h3>
                    <p>View your attendance records</p>
                </div>

                <i class="fas fa-chevron-right card-arrow"></i>

            </a>


            <!-- Leave -->
            <a href="index.php?url=employee-leave-request" class="dashboard-card">

                <div class="dashboard-card-icon leave">
                    <i class="fas fa-calendar-alt"></i>
                </div>

                <div class="dashboard-card-content">
                    <h3>Leave Request</h3>
                    <p>Submit and track leave requests</p>
                </div>

                <i class="fas fa-chevron-right card-arrow"></i>

            </a>


            <!-- Payroll -->
            <a href="index.php?url=payroll" class="dashboard-card">

                <div class="dashboard-card-icon payroll">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>

                <div class="dashboard-card-content">
                    <h3>Payroll</h3>
                    <p>View your payslip information</p>
                </div>

                <i class="fas fa-chevron-right card-arrow"></i>

            </a>


            <!-- Benefits -->
            <a href="index.php?url=benefits-and-government-contribution" class="dashboard-card">

                <div class="dashboard-card-icon benefits">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>

                <div class="dashboard-card-content">
                    <h3>Benefits & Contributions</h3>
                    <p>View your government contributions</p>
                </div>

                <i class="fas fa-chevron-right card-arrow"></i>

            </a>


            <!-- Performance -->
            <a href="index.php?url=performance" class="dashboard-card">

                <div class="dashboard-card-icon performance">
                    <i class="fas fa-chart-line"></i>
                </div>

                <div class="dashboard-card-content">
                    <h3>Performance</h3>
                    <p>View your performance evaluation</p>
                </div>

                <i class="fas fa-chevron-right card-arrow"></i>

            </a>


            <!-- Training -->
            <a href="index.php?url=learning-and-development" class="dashboard-card">

                <div class="dashboard-card-icon training">
                    <i class="fas fa-graduation-cap"></i>
                </div>

                <div class="dashboard-card-content">
                    <h3>Training & Development</h3>
                    <p>View your training records</p>
                </div>

                <i class="fas fa-chevron-right card-arrow"></i>

            </a>

        </div>

    </section>

    <section class="dashboard-section">

        <div class="dashboard-section-header">
            <div>
                <span>EMPLOYEE RELATIONS</span>
                <h2>Requests & Support</h2>
            </div>
        </div>


        <div class="request-grid">

            <a href="index.php?url=complaint" class="request-card">

                <i class="fas fa-comment-alt"></i>

                <div>
                    <h3>Employee Complaint</h3>
                    <p>Submit or view your complaints</p>
                </div>

            </a>


            <a href="index.php?url=grievance" class="request-card">

                <i class="fas fa-scale-balanced"></i>

                <div>
                    <h3>Grievance</h3>
                    <p>Submit and monitor grievances</p>
                </div>

            </a>


            <a href="index.php?url=resignation-request" class="request-card">

                <i class="fas fa-user-minus"></i>

                <div>
                    <h3>Resignation Request</h3>
                    <p>Manage your resignation request</p>
                </div>

            </a>

        </div>

    </section>
</div>