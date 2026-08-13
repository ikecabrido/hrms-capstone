<div class="sidebar-header d-flex flex-column align-items-center justify-content-center">



    <div class="profile-avatar" style="
    width:80px;
    height:80px;
    border-radius:50%;
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#575656;
    color:#fff;
    font-size:32px;
    font-weight:600;
    flex-shrink:0;
    box-shadow:0 3px 10px rgba(0,0,0,.15);
">
        <?php if (!empty($employeeProfileInfo['profile_image'])): ?>
            <img src="/hrms-capstone/modules/portal/public/assets/uploads/profile/<?= htmlspecialchars($employeeProfileInfo['profile_image']) ?>"
                alt="Profile Photo" style="
                width:100%;
                height:100%;
                object-fit:cover;
                display:block;
            ">
        <?php else: ?>
            <?= htmlspecialchars($employeeInitial) ?>
        <?php endif; ?>
    </div>

    <h1 class="employee_name fs-6">
        <?= htmlspecialchars($employeeName) ?>
    </h1>

    <p class="employee_position fs-6">
        <?= htmlspecialchars($employeePosition) ?>
    </p>

</div>