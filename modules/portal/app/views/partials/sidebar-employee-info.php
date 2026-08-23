<div class="sidebar-header d-flex flex-column align-items-center justify-content-center">

    <div class="profile-avatar">

        <?php if (!empty($employeeProfileInfo['profile_image'])): ?>

            <img src="/hrms-capstone/modules/portal/public/assets/uploads/profile/<?= htmlspecialchars(
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
    <?php if (!empty($employeeName)): ?>
        <h1 class="employee_name fs-6">
            <?= htmlspecialchars($employeeName) ?>
        </h1>
    <?php else: ?>
        <span class="text-white">no employee data!</span>
    <?php endif; ?>


    <p class="employee_position fs-6">
        <?php if ($_SESSION['is_admin'] == true): ?>
            <span>HR Admin</span>
        <?php else: ?>
            <?= htmlspecialchars($employeePosition) ?>
        <?php endif; ?>
    </p>

</div>