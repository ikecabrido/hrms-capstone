<div class="employee-dashboard">

    <!-- =====================================================
         WELCOME SECTION
         ===================================================== -->
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

    <!-- =====================================================
         RANDOM EMPLOYEE IMAGE
         ===================================================== -->
    <section class="dashboard-banner">

        <img id="employeeBanner" src="https://random.imagecdn.app/v1/image?width=1200&height=350&category=business"
            alt="Employee workplace">

        <div class="banner-overlay">
            <div>
                <span>EMPLOYEE SERVICES</span>
                <h2>Your work information in one place.</h2>
            </div>
        </div>

    </section>


    <!-- =====================================================
         QUICK ACCESS
         ===================================================== -->
    <section class="dashboard-section">

        <div class="dashboard-section-header">
            <div>
                <span>QUICK ACCESS</span>
                <h2>Employee Services</h2>
            </div>
        </div>


        <div class="quick-access-grid">

            <!-- Attendance -->
            <a href="index.php?url=employee-attendance-index" class="dashboard-card">

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
                    <h3>Leave Management</h3>
                    <p>Submit and track leave requests</p>
                </div>

                <i class="fas fa-chevron-right card-arrow"></i>

            </a>


            <!-- Payroll -->
            <a href="index.php?url=employee-payslip-items" class="dashboard-card">

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
            <a href="index.php?url=benefits-and-gov-contrib" class="dashboard-card">

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
            <a href="index.php?url=performance-feedback" class="dashboard-card">

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


    <!-- =====================================================
         EMPLOYEE REQUESTS
         ===================================================== -->
    <section class="dashboard-section">

        <div class="dashboard-section-header">
            <div>
                <span>EMPLOYEE RELATIONS</span>
                <h2>Requests & Support</h2>
            </div>
        </div>


        <div class="request-grid">

            <a href="index.php?url=employee-complaint-index" class="request-card">

                <i class="fas fa-comment-alt"></i>

                <div>
                    <h3>Employee Complaint</h3>
                    <p>Submit or view your complaints</p>
                </div>

            </a>


            <a href="index.php?url=employee-grievance" class="request-card">

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
<?php require __DIR__ . '/../partials/notification.php'; ?>