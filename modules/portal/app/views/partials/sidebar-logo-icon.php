<div class="school-logo">

    <img src="/hrms-capstone/modules/portal/public/assets/images/bcp-logo.png" alt="School Logo">

    <div class="sidebar-icons">

        <!-- Notification -->
        <div class="dropdown">

            <?php
            $notifications = $employeeNotification ?? [];

            $unreadCount = 0;

            foreach ($notifications as $notification) {
                if ((int) ($notification['is_read'] ?? 0) === 0) {
                    $unreadCount++;
                }
            }
            ?>

            <!-- Bell -->
            <button type="button" class="btn p-0 border-0 position-relative" id="bellBtn" data-bs-toggle="dropdown"
                aria-expanded="false">
                <i class="fa-regular fa-bell" style="color:aliceblue;"></i>

                <?php if ($unreadCount > 0): ?>

                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="
                    font-size:8px;
                    min-width:16px;
                    height:16px;
                    padding:3px 4px;
                ">
                        <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
                    </span>

                <?php endif; ?>

            </button>


            <!-- Dropdown -->
            <div class="dropdown-menu dropdown-menu-end p-0 shadow-sm" aria-labelledby="bellBtn" style="
            width:350px;
            max-width:calc(100vw - 30px);
            border:1px solid #e5e7eb;
            border-radius:12px;
            overflow:hidden;
        ">

                <!-- HEADER -->
                <div class="dropdown-header d-flex justify-content-between align-items-center" style="
                padding:13px 15px;
                background:#f8fafc;
                border-bottom:1px solid #e5e7eb;
            ">

                    <div>
                        <div style="
                    color:#111827;
                    font-size:13px;
                    font-weight:700;
                ">
                            Notifications
                        </div>

                        <div style="
                    margin-top:2px;
                    color:#9ca3af;
                    font-size:9px;
                ">
                            <?= count($notifications) ?> total
                        </div>
                    </div>


                    <?php if ($unreadCount > 0): ?>

                        <a href="index.php?url=notification-mark-all-read" style="
                        color:#2563eb;
                        text-decoration:none;
                        font-size:10px;
                        font-weight:600;
                    ">
                            Mark all as read
                        </a>

                    <?php endif; ?>

                </div>


                <!-- NOTIFICATION LIST -->
                <div class="notification-list" style="
                max-height:360px;
                overflow-y:auto;
            ">

                    <?php if (!empty($notifications)): ?>

                        <?php foreach (array_slice($notifications, 0, 5) as $notification): ?>

                            <?php
                            $isUnread = (int) ($notification['is_read'] ?? 0) === 0;

                            $type = strtolower(
                                $notification['type'] ?? 'general'
                            );

                            $icon = match ($type) {

                                'announcement' => 'fa-bullhorn',

                                'payroll' => 'fa-file-invoice-dollar',

                                'leave' => 'fa-calendar-alt',

                                'training' => 'fa-graduation-cap',

                                'performance' => 'fa-chart-line',

                                'document' => 'fa-file-alt',

                                'meeting' => 'fa-users',

                                'compliance' => 'fa-shield-alt',

                                default => 'fa-bell'
                            };
                            ?>


                            <a href="index.php?url=notification" class="dropdown-item" style="
                            display:flex;
                            align-items:flex-start;
                            gap:10px;

                            padding:12px 14px;

                            white-space:normal;

                            border-bottom:1px solid #f1f5f9;

                            background:<?= $isUnread
                                ? '#f8fbff'
                                : '#fff' ?>;

                            text-decoration:none;
                        ">

                                <!-- ICON -->
                                <div style="
                            width:34px;
                            height:34px;
                            min-width:34px;

                            display:flex;
                            align-items:center;
                            justify-content:center;

                            border-radius:9px;

                            background:#eff6ff;
                            color:#2563eb;

                            font-size:12px;
                        ">
                                    <i class="fas <?= $icon ?>"></i>
                                </div>


                                <!-- CONTENT -->
                                <div style="
                            flex:1;
                            min-width:0;
                        ">

                                    <div style="
                                display:flex;
                                align-items:center;
                                gap:6px;
                            ">

                                        <strong style="
                                    color:#111827;
                                    font-size:11px;
                                    font-weight:<?= $isUnread
                                        ? '700'
                                        : '600' ?>;

                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                    white-space:nowrap;
                                ">
                                            <?= htmlspecialchars(
                                                $notification['title']
                                                ?? 'Notification'
                                            ) ?>
                                        </strong>


                                        <?php if ($isUnread): ?>

                                            <span style="
                                        width:6px;
                                        height:6px;
                                        min-width:6px;
                                        border-radius:50%;
                                        background:#2563eb;
                                    "></span>

                                        <?php endif; ?>

                                    </div>


                                    <div style="
                                margin-top:3px;

                                color:#6b7280;

                                font-size:9px;
                                line-height:1.4;

                                display:-webkit-box;
                                -webkit-line-clamp:2;
                                -webkit-box-orient:vertical;
                                overflow:hidden;
                            ">
                                        <?= htmlspecialchars(
                                            $notification['message'] ?? ''
                                        ) ?>
                                    </div>


                                    <div style="
                                margin-top:4px;

                                color:#9ca3af;

                                font-size:8px;
                            ">
                                        <i class="far fa-clock"></i>

                                        <?= !empty($notification['created_at'])
                                            ? date(
                                                'M d, Y h:i A',
                                                strtotime(
                                                    $notification['created_at']
                                                )
                                            )
                                            : '-' ?>
                                    </div>

                                </div>

                            </a>

                        <?php endforeach; ?>


                    <?php else: ?>

                        <!-- EMPTY -->
                        <div style="
                    padding:35px 15px;
                    text-align:center;
                ">

                            <div style="
                        width:42px;
                        height:42px;

                        margin:0 auto 10px;

                        display:flex;
                        align-items:center;
                        justify-content:center;

                        border-radius:11px;

                        background:#eff6ff;
                        color:#93c5fd;

                        font-size:17px;
                    ">
                                <i class="far fa-bell"></i>
                            </div>

                            <div style="
                        color:#374151;
                        font-size:11px;
                        font-weight:600;
                    ">
                                No Notifications
                            </div>

                            <div style="
                        margin-top:3px;
                        color:#9ca3af;
                        font-size:9px;
                    ">
                                You're all caught up.
                            </div>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- FOOTER -->
                <div class="text-center" style="
                padding:10px;
                background:#fff;
                border-top:1px solid #e5e7eb;
            ">

                    <a href="index.php?url=notification" style="
                    color:#2563eb;
                    text-decoration:none;
                    font-size:10px;
                    font-weight:600;
                ">
                        View all notifications
                        <i class="fas fa-arrow-right ms-1"></i>
                    </a>

                </div>

            </div>

        </div>

        <!-- User -->
        <div class="dropdown">

            <button type="button" class="btn p-0 border-0" id="userBtn" data-bs-toggle="dropdown"
                data-bs-auto-close="true" aria-expanded="false">
                <i class="fa-regular fa-circle-user" style="color: aliceblue;"></i>
            </button>

            <div class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="userBtn" style="width: 350px;">

                <!-- User Information -->
                <div class="dropdown-header d-flex align-items-center p-2">

                    <div class="d-flex align-items-center gap-2">

                        <div class="dropdown-avatar d-flex align-items-center justify-content-center" style="
                                width: 35px;
                                height: 35px;
                                border-radius: 50%;
                                background: #575656;
                                color: white;
                                font-weight: 600;
                                font-size: 16px;
                            ">
                            <?= htmlspecialchars($employeeInitial) ?>
                        </div>

                        <div>

                            <strong class="d-block">
                                <?= htmlspecialchars($employeeName) ?>
                            </strong>

                            <small class="text-muted">
                                <?= htmlspecialchars($employeePosition) ?>
                            </small>

                        </div>

                    </div>

                </div>

                <div class="dropdown-divider m-0"></div>

                <!-- Menu -->
                <a href="index.php?url=user-profile" class="dropdown-item">
                    <i class="fa-regular fa-user me-2"></i>
                    Profile Settings
                </a>

                <a href="index.php?url=user-profile&modal=change-password" class="dropdown-item">
                    <i class="fa-solid fa-lock me-2"></i>
                    Change Password
                </a>

                <div class="dropdown-divider m-0"></div>

                <!-- Sign Out -->
                <a href="/hrms-capstone/modules/portal/index.php?url=auth-logout" class="dropdown-item text-danger">
                    <i class="fa-solid fa-right-from-bracket me-2"></i>
                    Sign Out
                </a>

            </div>
        </div>

    </div>
</div>