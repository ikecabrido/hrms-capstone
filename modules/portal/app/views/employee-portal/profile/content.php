<div class="employee-dashboard">

    <section class="dashboard-welcome" id="dashboardWelcome">

        <!-- Animated background -->
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label" id="welcomeLabel">
                <i class="fas fa-circle"></i>
                EMPLOYEE PROFILE
            </span>

            <h1 id="welcomeTitle">
                Welcome to Profile Page,
                <span>
                    <?= htmlspecialchars(
                        $employeeProfileInfo['first_name']
                        ?? $userInfos['username']
                        ?? 'Employee'
                    ); ?>
                </span>
            </h1>

            <p id="welcomeDescription">
                View and manage your personal information, account details,
                and employee records.
            </p>

            <div class="welcome-line"></div>

        </div>

        <!-- Profile icon -->
        <div class="welcome-decoration" id="welcomeDecoration">
            <i class="fas fa-user-tie"></i>
        </div>

    </section>

    <section class="dashboard-section">

        <div class="dashboard-section-header">

            <div>
                <span>PERSONAL INFORMATION</span>
                <h2>My Profile</h2>
            </div>
        </div>
        <!-- =================================================
         PROFILE CARD
         ================================================= -->
        <div class="profile-card">

            <!-- Profile Header -->
            <div class="profile-card-header">

                <!-- Avatar -->
                <div class="profile-avatar">

                    <?php if (!empty($employeeProfileInfo['profile_image'])): ?>

                        <img src="/hrms-capstone/modules/portal/public/uploads/profile/<?= htmlspecialchars(
                            $employeeProfileInfo['profile_image']
                        ); ?>" alt="Profile Photo">

                    <?php else: ?>

                        <?= strtoupper(
                            substr(
                                $employeeProfileInfo['first_name']
                                ?? $userInfos['username']
                                ?? 'E',
                                0,
                                1
                            )
                        ); ?>

                    <?php endif; ?>

                </div>


                <!-- Employee Name -->
                <div class="profile-header-info">

                    <?php
                    $fullName = trim(
                        ($employeeProfileInfo['first_name'] ?? '') . ' ' .
                        ($employeeProfileInfo['middle_name'] ?? '') . ' ' .
                        ($employeeProfileInfo['last_name'] ?? '') .
                        (
                            !empty($employeeProfileInfo['suffix'])
                            ? ' ' . $employeeProfileInfo['suffix']
                            : ''
                        )
                    );

                    $fullName = $fullName !== ''
                        ? $fullName
                        : ($userInfos['username'] ?? 'Employee');
                    ?>

                    <h2>
                        <?= htmlspecialchars($fullName); ?>
                    </h2>

                    <p>
                        @<?= htmlspecialchars(
                            $userInfos['username'] ?? ''
                        ); ?>
                    </p>

                    <span class="profile-position">
                        <?= htmlspecialchars(
                            $employeeProfileInfo['position']
                            ?? $userInfos['role']
                            ?? 'Employee'
                        ); ?>
                    </span>

                </div>

            </div>

            <div class="profile-section">

                <div class="profile-section-title">
                    <i class="fas fa-user-circle"></i>

                    <div>
                        <span>ACCOUNT</span>
                        <h3>Account Information</h3>
                    </div>
                </div>


                <div class="profile-info-grid">

                    <div class="profile-info-item">
                        <span>Username</span>
                        <strong>
                            <?= htmlspecialchars(
                                $userInfos['username'] ?? 'N/A'
                            ); ?>
                        </strong>
                    </div>


                    <div class="profile-info-item">
                        <span>Email</span>
                        <strong>
                            <?= htmlspecialchars(
                                $userInfos['email'] ?? 'N/A'
                            ); ?>
                        </strong>
                    </div>


                    <div class="profile-info-item">
                        <span>Role</span>
                        <strong>
                            <?= htmlspecialchars(
                                $userInfos['role'] ?? 'Employee'
                            ); ?>
                        </strong>
                    </div>


                    <div class="profile-info-item">
                        <span>Account Status</span>

                        <?php if ((int) ($userInfos['is_active'] ?? 1) === 1): ?>

                            <strong class="status-active">
                                <i class="fas fa-circle"></i>
                                Active
                            </strong>

                        <?php else: ?>

                            <strong class="status-inactive">
                                <i class="fas fa-circle"></i>
                                Inactive
                            </strong>

                        <?php endif; ?>

                    </div>


                    <div class="profile-info-item">
                        <span>Account Created</span>
                        <strong>
                            <?= !empty($userInfos['created_at'])
                                ? date(
                                    'F d, Y',
                                    strtotime($userInfos['created_at'])
                                )
                                : 'N/A';
                            ?>
                        </strong>
                    </div>


                    <div class="profile-info-item">
                        <span>Theme</span>
                        <strong>
                            <?= ucfirst(
                                $userInfos['theme'] ?? 'Light'
                            ); ?>
                        </strong>
                    </div>

                </div>

            </div>

            <div class="profile-section">

                <div class="profile-section-title">

                    <i class="fas fa-id-card"></i>

                    <div>
                        <span>EMPLOYEE RECORD</span>
                        <h3>Employment Information</h3>
                    </div>

                </div>


                <div class="profile-info-grid">

                    <div class="profile-info-item">

                        <span>Employee Number</span>

                        <strong>
                            <?= htmlspecialchars(
                                $employeeProfileInfo['employee_num']
                                ?? 'N/A'
                            ); ?>
                        </strong>

                    </div>


                    <div class="profile-info-item">

                        <span>Department</span>

                        <strong>
                            <?= htmlspecialchars(
                                $employeeProfileInfo['department']
                                ?? 'N/A'
                            ); ?>
                        </strong>

                    </div>


                    <div class="profile-info-item">

                        <span>Position</span>

                        <strong>
                            <?= htmlspecialchars(
                                $employeeProfileInfo['position']
                                ?? 'N/A'
                            ); ?>
                        </strong>

                    </div>


                    <div class="profile-info-item">

                        <span>Employment Status</span>

                        <strong>
                            <?= htmlspecialchars(
                                $employeeProfileInfo['employment_status']
                                ?? 'N/A'
                            ); ?>
                        </strong>

                    </div>


                    <div class="profile-info-item">

                        <span>Employment Type</span>

                        <strong>
                            <?= htmlspecialchars(
                                $employeeProfileInfo['employment_type']
                                ?? 'N/A'
                            ); ?>
                        </strong>

                    </div>


                    <div class="profile-info-item">

                        <span>Hire Date</span>

                        <strong>
                            <?= !empty(
                                $employeeProfileInfo['hire_date']
                            )
                                ? date(
                                    'F d, Y',
                                    strtotime(
                                        $employeeProfileInfo['hire_date']
                                    )
                                )
                                : 'N/A';
                            ?>
                        </strong>

                    </div>

                </div>

            </div>

            <div class="profile-section">

                <div class="profile-section-title">

                    <i class="fas fa-address-card"></i>

                    <div>
                        <span>PERSONAL DETAILS</span>
                        <h3>Contact & Personal Information</h3>
                    </div>

                </div>


                <div class="profile-info-grid">

                    <div class="profile-info-item">

                        <span>Gender</span>

                        <strong>
                            <?= htmlspecialchars(
                                $employeeProfileInfo['gender']
                                ?? 'N/A'
                            ); ?>
                        </strong>

                    </div>


                    <div class="profile-info-item">

                        <span>Birth Date</span>

                        <strong>
                            <?= !empty(
                                $employeeProfileInfo['birth_date']
                            )
                                ? date(
                                    'F d, Y',
                                    strtotime(
                                        $employeeProfileInfo['birth_date']
                                    )
                                )
                                : 'N/A';
                            ?>
                        </strong>

                    </div>


                    <div class="profile-info-item">

                        <span>Civil Status</span>

                        <strong>
                            <?= htmlspecialchars(
                                $employeeProfileInfo['civil_status']
                                ?? 'N/A'
                            ); ?>
                        </strong>

                    </div>


                    <div class="profile-info-item">

                        <span>Citizenship</span>

                        <strong>
                            <?= htmlspecialchars(
                                $employeeProfileInfo['citizenship']
                                ?? 'N/A'
                            ); ?>
                        </strong>

                    </div>


                    <div class="profile-info-item">

                        <span>Mobile Number</span>

                        <strong>
                            <?= htmlspecialchars(
                                $employeeProfileInfo['mobile_no']
                                ?? 'N/A'
                            ); ?>
                        </strong>

                    </div>


                    <div class="profile-info-item">

                        <span>Phone Number</span>

                        <strong>
                            <?= htmlspecialchars(
                                $employeeProfileInfo['phone_no']
                                ?? 'N/A'
                            ); ?>
                        </strong>

                    </div>


                    <div class="profile-info-item profile-info-full">

                        <span>Current Address</span>

                        <strong>
                            <?= htmlspecialchars(
                                $employeeProfileInfo['current_address']
                                ?? 'N/A'
                            ); ?>
                        </strong>

                    </div>

                </div>

            </div>

            <div class="profile-actions">

                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#changePasswordModal">

                    <i class="fas fa-key"></i>
                    Change Password

                </button>

            </div>

        </div>

    </section>
</div>

<?php require __DIR__ . '/../../partials/notification.php'; ?>
<?php require __DIR__ . '/change-password.php'; ?>