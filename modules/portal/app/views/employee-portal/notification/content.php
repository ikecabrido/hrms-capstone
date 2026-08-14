<div class="employee-dashboard">

    <section class="dashboard-welcome" id="notificationWelcome">
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                EMPLOYEE NOTIFICATIONS
            </span>

            <h1 class="welcome-title">
                Notifications
            </h1>

            <p class="welcome-description">
                Stay updated with important announcements, HR updates, reminders, and other notifications related to
                your employee services.
            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration">
            <i class="fas fa-bell"></i>
        </div>
    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section class="dashboard-section" style="
    width:100%;
    box-sizing:border-box;
">

        <?php
        $notifications = $employeeNotification ?? [];

        $totalNotifications = count($notifications);
        $unreadCount = 0;

        foreach ($notifications as $notification) {
            if ((int) ($notification['is_read'] ?? 0) === 0) {
                $unreadCount++;
            }
        }
        ?>

        <!-- HEADER -->
        <div style="
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    margin-bottom:20px;
    padding-bottom:16px;
    border-bottom:1px solid #e5e7eb;
    flex-wrap:wrap;
">

            <!-- TITLE -->
            <div style="
        min-width:240px;
        flex:1;
    ">

                <span style="
            display:block;
            margin-bottom:5px;
            color:#2563eb;
            font-size:9px;
            font-weight:700;
            letter-spacing:.08em;
        ">
                    NOTIFICATION CENTER
                </span>

                <h2 style="
            margin:0;
            color:#111827;
            font-size:22px;
            font-weight:700;
            line-height:1.3;
        ">
                    My Notifications
                </h2>

                <p style="
            margin:5px 0 0;
            color:#6b7280;
            font-size:11px;
            line-height:1.5;
        ">
                    Stay updated with important messages and announcements from HR.
                </p>

            </div>


            <!-- ACTIONS -->
            <div style="
        display:flex;
        align-items:center;
        gap:8px;
        flex-wrap:wrap;
    ">

                <!-- COUNT -->
                <div style="
            display:inline-flex;
            align-items:center;
            gap:7px;
            padding:8px 12px;
            border:1px solid #e5e7eb;
            border-radius:9px;
            background:#f8fafc;
            color:#374151;
            font-size:10px;
            font-weight:600;
            white-space:nowrap;
        ">

                    <i class="fas fa-bell" style="
                    color:#2563eb;
                    font-size:11px;
                "></i>

                    <span>
                        <?= $totalNotifications ?>
                        <?= $totalNotifications == 1 ? 'Notification' : 'Notifications' ?>
                    </span>

                    <?php if ($unreadCount > 0): ?>

                        <span style="
                    display:inline-flex;
                    align-items:center;
                    justify-content:center;
                    min-width:20px;
                    padding:3px 6px;
                    border-radius:20px;
                    background:#2563eb;
                    color:#fff;
                    font-size:8px;
                    font-weight:700;
                ">
                            <?= $unreadCount ?> New
                        </span>

                    <?php endif; ?>

                </div>


                <!-- MARK ALL -->
                <?php if ($unreadCount > 0): ?>

                    <form action="index.php?url=notification-mark-all-read" method="POST" style="margin:0;">

                        <button type="submit" style="
                        display:inline-flex;
                        align-items:center;
                        justify-content:center;
                        gap:6px;

                        padding:8px 11px;

                        border:1px solid #dbeafe;
                        border-radius:9px;

                        background:#eff6ff;
                        color:#2563eb;

                        font-size:10px;
                        font-weight:600;

                        cursor:pointer;
                        white-space:nowrap;

                        transition:.2s ease;
                    " onmouseover="
                        this.style.background='#dbeafe';
                        this.style.borderColor='#bfdbfe';
                    " onmouseout="
                        this.style.background='#eff6ff';
                        this.style.borderColor='#dbeafe';
                    ">
                            <i class="fas fa-check-double"></i>
                            Mark all as read
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </div>


        <?php if (!empty($notifications)): ?>

            <!-- NOTIFICATION LIST -->
            <div style="
            display:flex;
            flex-direction:column;
            gap:12px;
        ">

                <?php foreach ($notifications as $notification): ?>

                    <?php
                    $notificationId = (int) ($notification['notification_id'] ?? 0);

                    $title = $notification['title'] ?? 'Notification';

                    $message = $notification['message'] ?? '';

                    $type = strtolower(
                        $notification['type'] ?? 'general'
                    );

                    $priority = strtolower(
                        $notification['priority'] ?? 'normal'
                    );

                    $isRead = (int) (
                        $notification['is_read'] ?? 0
                    ) === 1;

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

                    $color = match ($priority) {

                        'urgent' => '#dc2626',

                        'important' => '#d97706',

                        default => '#2563eb'
                    };


                    $background = match ($priority) {

                        'urgent' => '#fef2f2',

                        'important' => '#fffbeb',

                        default => '#eff6ff'
                    };
                    ?>


                    <!-- NOTIFICATION -->
                    <div style="
                    position:relative;

                    display:flex;
                    align-items:flex-start;

                    gap:14px;

                    width:100%;

                    padding:18px;

                    box-sizing:border-box;

                    border:1px solid <?= $isRead
                        ? '#e5e7eb'
                        : '#bfdbfe' ?>;

                    border-radius:13px;

                    background:<?= $isRead
                        ? '#ffffff'
                        : '#f8fbff' ?>;

                    transition:.2s ease;
                " onmouseover="
                    this.style.borderColor='#93c5fd';
                    this.style.background='#f8fafc';
                " onmouseout="
                    this.style.borderColor='<?= $isRead
                        ? '#e5e7eb'
                        : '#bfdbfe' ?>';

                    this.style.background='<?= $isRead
                        ? '#ffffff'
                        : '#f8fbff' ?>';
                ">


                        <!-- UNREAD INDICATOR -->
                        <?php if (!$isRead): ?>

                            <span style="
                            position:absolute;
                            top:15px;
                            right:15px;

                            width:8px;
                            height:8px;

                            border-radius:50%;

                            background:#2563eb;
                        "></span>

                        <?php endif; ?>


                        <!-- ICON -->
                        <div style="
                        width:46px;
                        height:46px;
                        min-width:46px;

                        display:flex;
                        align-items:center;
                        justify-content:center;

                        border-radius:12px;

                        background:<?= $background ?>;
                        color:<?= $color ?>;

                        font-size:17px;
                    ">
                            <i class="fas <?= $icon ?>"></i>
                        </div>


                        <!-- CONTENT -->
                        <div style="
                        flex:1;
                        min-width:0;
                    ">

                            <!-- TITLE -->
                            <div style="
                            display:flex;
                            align-items:center;
                            gap:8px;

                            margin-bottom:6px;

                            padding-right:15px;

                            flex-wrap:wrap;
                        ">

                                <h3 style="
                                margin:0;

                                color:#111827;

                                font-size:14px;

                                font-weight:<?= $isRead
                                    ? '600'
                                    : '700' ?>;
                            ">
                                    <?= htmlspecialchars($title) ?>
                                </h3>


                                <?php if ($priority !== 'normal'): ?>

                                    <span style="
                                    padding:3px 8px;

                                    border-radius:20px;

                                    background:<?= $priority === 'urgent'
                                        ? '#fef2f2'
                                        : '#fffbeb' ?>;

                                    color:<?= $color ?>;

                                    font-size:8px;

                                    font-weight:700;

                                    text-transform:uppercase;
                                ">
                                        <?= htmlspecialchars($priority) ?>
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- MESSAGE -->
                            <p style="
                            margin:0;

                            color:#4b5563;

                            font-size:12px;

                            line-height:1.6;
                        ">
                                <?= htmlspecialchars($message) ?>
                            </p>


                            <!-- META -->
                            <div style="
                            display:flex;
                            align-items:center;

                            gap:14px;

                            margin-top:10px;

                            color:#9ca3af;

                            font-size:9px;

                            flex-wrap:wrap;
                        ">

                                <!-- DATE -->
                                <span>
                                    <i class="far fa-clock"></i>

                                    <?= !empty($notification['created_at'])
                                        ? date(
                                            'M d, Y h:i A',
                                            strtotime(
                                                $notification['created_at']
                                            )
                                        )
                                        : '-' ?>
                                </span>


                                <!-- TYPE -->
                                <span>
                                    <i class="fas fa-tag"></i>

                                    <?= htmlspecialchars(
                                        ucfirst($type)
                                    ) ?>
                                </span>


                                <!-- STATUS -->
                                <?php if ($isRead): ?>

                                    <span style="
                                    color:#16a34a;
                                ">
                                        <i class="fas fa-check-circle"></i>
                                        Read

                                        <?php if (!empty($notification['read_at'])): ?>

                                            ·

                                            <?= date(
                                                'M d, Y h:i A',
                                                strtotime(
                                                    $notification['read_at']
                                                )
                                            ) ?>

                                        <?php endif; ?>

                                    </span>

                                <?php else: ?>

                                    <span style="
                                    color:#2563eb;
                                    font-weight:600;
                                ">
                                        <i class="fas fa-circle" style="font-size:5px;"></i>

                                        Unread
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- MARK AS READ -->
                        <?php if (!$isRead): ?>

                            <form action="index.php?url=notification-mark-read" method="POST" style="margin:0;">
                                <input type="hidden" name="notification_id" value="<?= (int) $notificationId ?>">

                                <button type="submit" title="Mark as read" style="
            width:32px;
            height:32px;
            min-width:32px;

            display:flex;
            align-items:center;
            justify-content:center;

            padding:0;

            border:1px solid #bfdbfe;
            border-radius:8px;

            background:#eff6ff;
            color:#2563eb;

            cursor:pointer;
            font-size:10px;
        ">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </div>


        <?php else: ?>


            <!-- EMPTY STATE -->
            <div style="
            width:100%;

            padding:60px 20px;

            box-sizing:border-box;

            text-align:center;

            border:1px solid #e5e7eb;

            border-radius:13px;

            background:#fff;
        ">

                <div style="
                width:64px;
                height:64px;

                margin:0 auto 15px;

                display:flex;
                align-items:center;
                justify-content:center;

                border-radius:16px;

                background:#eff6ff;

                color:#93c5fd;

                font-size:25px;
            ">
                    <i class="far fa-bell"></i>
                </div>


                <h3 style="
                margin:0 0 6px;

                color:#374151;

                font-size:15px;

                font-weight:700;
            ">
                    No Notifications
                </h3>


                <p style="
                margin:0;

                color:#9ca3af;

                font-size:11px;
            ">
                    You're all caught up. There are no notifications available.
                </p>

            </div>

        <?php endif; ?>

    </section>

</div>