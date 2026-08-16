<div class="employee-dashboard">

    <section class="dashboard-welcome" id="dashboardWelcome">

        <!-- Decorative animated background -->
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label" id="welcomeLabel">
                <i class="fas fa-circle"></i>
                ADMIN PORTAL
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
    
</div>