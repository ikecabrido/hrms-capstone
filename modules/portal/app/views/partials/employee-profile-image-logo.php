<?php if (!empty($employeeImage['profile_image'])): ?>

    <img
        src="<?= asset('assets/uploads/profile/' . htmlspecialchars($employeeImage['profile_image'], ENT_QUOTES, 'UTF-8')) ?>"
        alt="Profile Photo"
    >

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