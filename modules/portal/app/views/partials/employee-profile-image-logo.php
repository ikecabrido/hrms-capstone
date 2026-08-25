<?php if (!empty($employeeImage['profile_image'])): ?>

    <img src="/hrms-capstone/modules/portal/public/assets/uploads/profile/<?= htmlspecialchars(
        $employeeImage['profile_image']
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