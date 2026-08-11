<div class="school-logo">

    <img src="/hrms-capstone/modules/portal/public/assets/images/bcp-logo.png" alt="School Logo">

    <div class="sidebar-icons">

        <!-- Notification -->
        <div class="dropdown">

            <button type="button" class="btn p-0 border-0" id="bellBtn" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-regular fa-bell" style="color: aliceblue;"></i>
            </button>

            <div class="dropdown-menu p-0" aria-labelledby="bellBtn">

                <div class="dropdown-header d-flex justify-content-between align-items-center">
                    <span>Notifications</span>

                    <button type="button" class="btn btn-sm btn-link p-0">
                        Mark all as read
                    </button>
                </div>

                <div class="notification-list">

                    <a href="#" class="dropdown-item">
                        <strong>Welcome!</strong>
                        <br>
                        <small>You have a new notification.</small>
                    </a>

                    <a href="#" class="dropdown-item">
                        <strong>System Update</strong>
                        <br>
                        <small>Your employee portal is ready.</small>
                    </a>

                </div>

                <div class="dropdown-divider m-0"></div>

                <div class="text-center p-2">
                    <a href="#" class="text-decoration-none">
                        View all notifications
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
                <a href="#" class="dropdown-item">
                    <i class="fa-regular fa-user me-2"></i>
                    Profile Settings
                </a>

                <a href="#" class="dropdown-item">
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