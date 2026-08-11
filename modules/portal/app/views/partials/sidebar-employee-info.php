<div class="sidebar-header d-flex flex-column align-items-center justify-content-center">

    <div class="dropdown-avatar d-flex align-items-center justify-content-center mb-2" style="
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #575656;
            color: white;
            font-weight: 600;
            font-size: 40px;
        ">
        <?= htmlspecialchars($employeeInitial) ?>
    </div>

    <h1 class="employee_name fs-6">
        <?= htmlspecialchars($employeeName) ?>
    </h1>

    <p class="employee_position fs-6">
        <?= htmlspecialchars($employeePosition) ?>
    </p>

</div>