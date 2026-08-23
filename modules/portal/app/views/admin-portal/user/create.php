<div class="modal fade" id="createAccountModal<?= (int) $employee['id'] ?>" tabindex="-1"
    aria-labelledby="createAccountModalLabel<?= (int) $employee['id'] ?>" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">

        <div class="modal-content overflow-hidden" style="
                border:1px solid #e2e8f0;
                border-radius:16px;
                background:#fff;
                box-shadow:0 20px 45px rgba(15,23,42,.14),0 4px 12px rgba(15,23,42,.06);
            ">

            <form action="index.php?url=admin-user-store" method="POST">

                <!-- HEADER -->
                <div class="modal-header border-0" style="padding:20px 22px 14px;">

                    <div class="d-flex align-items-center gap-3">

                        <div class="d-flex align-items-center justify-content-center flex-shrink-0" style="
                                width:38px;
                                height:38px;
                                border-radius:10px;
                                background:#eef2ff;
                                color:#4f46e5;
                            ">
                            <i class="fa-solid fa-user-plus" style="font-size:13px;"></i>
                        </div>

                        <div>
                            <h5 class="modal-title mb-0" id="createAccountModalLabel<?= (int) $employee['id'] ?>" style="
                                    color:#0f172a;
                                    font-size:15px;
                                    font-weight:700;
                                    letter-spacing:-.01em;
                                ">
                                Create Account
                            </h5>

                            <div style="
                                margin-top:2px;
                                color:#64748b;
                                font-size:11px;
                            ">
                                Create login credentials for this employee
                            </div>
                        </div>

                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="
                            opacity:.45;
                            transform:scale(.8);
                        ">
                    </button>

                </div>


                <!-- BODY -->
                <div class="modal-body" style="padding:8px 22px 18px;">

                    <!-- EMPLOYEE SUMMARY -->
                    <div class="d-flex align-items-center" style="
                            gap:12px;
                            padding:11px 12px;
                            margin-bottom:18px;
                            border:1px solid #e8edf3;
                            border-radius:10px;
                            background:#f8fafc;
                        ">

                        <!-- PROFILE IMAGE -->
                        <?php if (!empty($employee['profile_image'])): ?>

                            <img src="/hrms-capstone/modules/portal/public/assets/uploads/profile/<?= htmlspecialchars($employee['profile_image']) ?>"
                                alt="Profile Photo" style="
                                    width:42px;
                                    height:42px;
                                    flex:0 0 42px;
                                    border-radius:50%;
                                    object-fit:cover;
                                ">

                        <?php else: ?>

                            <div class="d-flex align-items-center justify-content-center" style="
                                    width:42px;
                                    height:42px;
                                    flex:0 0 42px;
                                    border-radius:50%;
                                    background:#eef2ff;
                                    color:#4f46e5;
                                    font-size:12px;
                                    font-weight:700;
                                ">
                                <?= htmlspecialchars($initials) ?>
                            </div>

                        <?php endif; ?>


                        <!-- EMPLOYEE DETAILS -->
                        <div class="min-w-0">

                            <div class="text-truncate" style="
                                    color:#1e293b;
                                    font-size:12px;
                                    font-weight:650;
                                ">
                                <?= htmlspecialchars($fullName) ?>
                            </div>

                            <div class="text-truncate" style="
                                    margin-top:3px;
                                    color:#94a3b8;
                                    font-size:10px;
                                ">
                                <?= htmlspecialchars($employee['employee_num'] ?? '-') ?>

                                <span class="mx-1">•</span>

                                <?= htmlspecialchars($employee['department'] ?? '-') ?>
                            </div>

                        </div>

                    </div>


                    <input type="hidden" name="employee_id" value="<?= (int) $employee['id'] ?>">

                    <input type="hidden" name="role" value="employee">


                    <!-- USERNAME -->
                    <div style="margin-bottom:14px;">

                        <label for="username<?= (int) $employee['id'] ?>" style="
                                display:block;
                                margin-bottom:5px;
                                color:#334155;
                                font-size:11px;
                                font-weight:600;
                            ">
                            Username
                        </label>

                        <div class="input-group" style="height:38px;">

                            <span class="input-group-text" style="
                                    min-width:38px;
                                    justify-content:center;
                                    padding:0;
                                    border-color:#e2e8f0;
                                    background:#fff;
                                    color:#94a3b8;
                                ">
                                <i class="fa-solid fa-user" style="font-size:10px;"></i>
                            </span>

                            <input type="text" id="username<?= (int) $employee['id'] ?>" name="username"
                                class="form-control" placeholder="Enter username" required autocomplete="off" style="
                                    height:38px;
                                    border-color:#e2e8f0;
                                    color:#334155;
                                    font-size:11px;
                                    box-shadow:none;
                                ">

                        </div>

                    </div>


                    <!-- EMAIL -->
                    <div style="margin-bottom:14px;">

                        <label for="email<?= (int) $employee['id'] ?>" style="
                                display:block;
                                margin-bottom:5px;
                                color:#334155;
                                font-size:11px;
                                font-weight:600;
                            ">
                            Email Address
                        </label>

                        <div class="input-group" style="height:38px;">

                            <span class="input-group-text" style="
                                    min-width:38px;
                                    justify-content:center;
                                    padding:0;
                                    border-color:#e2e8f0;
                                    background:#fff;
                                    color:#94a3b8;
                                ">
                                <i class="fa-solid fa-envelope" style="font-size:10px;"></i>
                            </span>

                            <input type="email" id="email<?= (int) $employee['id'] ?>" name="email" class="form-control"
                                placeholder="employee@example.com"
                                value="<?= htmlspecialchars($employee['email'] ?? '') ?>" required style="
                                    height:38px;
                                    border-color:#e2e8f0;
                                    color:#334155;
                                    font-size:11px;
                                    box-shadow:none;
                                ">

                        </div>

                    </div>

<div style="margin-bottom:14px;">

    <label for="password<?= (int) $employee['id'] ?>" style="
        display:block;
        margin-bottom:5px;
        color:#334155;
        font-size:11px;
        font-weight:600;
    ">
        Temporary Password
    </label>

    <div class="input-group" style="height:38px;">

        <span class="input-group-text" style="
            width:38px;
            justify-content:center;
            padding:0;
            border-color:#e2e8f0;
            background:#fff;
            color:#94a3b8;
        ">
            <i class="fa-solid fa-lock" style="font-size:10px;"></i>
        </span>

        <input
            type="password"
            id="password<?= (int) $employee['id'] ?>"
            name="password"
            class="form-control"
            placeholder="Minimum 8 characters"
            minlength="8"
            required
            autocomplete="new-password"
            style="
                height:38px;
                border-color:#e2e8f0;
                color:#334155;
                font-size:11px;
                box-shadow:none;
            "
        >

        <button
            type="button"
            class="password-toggle-btn"
            data-password-target="password<?= (int) $employee['id'] ?>"
            aria-label="Show password"
            title="Show password"
            style="
                width:38px;
                padding:0;
                border:1px solid #e2e8f0;
                border-left:0;
                background:#fff;
                color:#94a3b8;
                display:flex;
                align-items:center;
                justify-content:center;
                cursor:pointer;
            "
        >
            <i class="fa-regular fa-eye" style="font-size:10px;"></i>
        </button>

    </div>

</div>


<!-- CONFIRM PASSWORD -->
<div style="margin-bottom:16px;">

    <label for="password_confirmation<?= (int) $employee['id'] ?>" style="
        display:block;
        margin-bottom:5px;
        color:#334155;
        font-size:11px;
        font-weight:600;
    ">
        Confirm Password
    </label>

    <div class="input-group" style="height:38px;">

        <span class="input-group-text" style="
            width:38px;
            justify-content:center;
            padding:0;
            border-color:#e2e8f0;
            background:#fff;
            color:#94a3b8;
        ">
            <i class="fa-solid fa-lock" style="font-size:10px;"></i>
        </span>

        <input
            type="password"
            id="password_confirmation<?= (int) $employee['id'] ?>"
            name="password_confirmation"
            class="form-control"
            placeholder="Re-enter password"
            minlength="8"
            required
            autocomplete="new-password"
            style="
                height:38px;
                border-color:#e2e8f0;
                color:#334155;
                font-size:11px;
                box-shadow:none;
            "
        >

        <button
            type="button"
            class="password-toggle-btn"
            data-password-target="password_confirmation<?= (int) $employee['id'] ?>"
            aria-label="Show password"
            title="Show password"
            style="
                width:38px;
                padding:0;
                border:1px solid #e2e8f0;
                border-left:0;
                background:#fff;
                color:#94a3b8;
                display:flex;
                align-items:center;
                justify-content:center;
                cursor:pointer;
            "
        >
            <i class="fa-regular fa-eye" style="font-size:10px;"></i>
        </button>

    </div>

</div>


                    <!-- INFO -->
                    <div class="d-flex align-items-start" style="
                            gap:9px;
                            padding:10px 11px;
                            border:1px solid #dbeafe;
                            border-radius:9px;
                            background:#eff6ff;
                            color:#2563eb;
                        ">

                        <i class="fa-solid fa-circle-info" style="
                                margin-top:2px;
                                font-size:10px;
                            ">
                        </i>

                        <span style="
                            font-size:10px;
                            line-height:1.5;
                        ">
                            These credentials will be used to access the employee portal.
                        </span>

                    </div>

                </div>


                <!-- FOOTER -->
                <div class="modal-footer" style="
                        display:flex;
                        justify-content:flex-end;
                        gap:8px;
                        padding:13px 22px 18px;
                        border-top:1px solid #f1f5f9;
                    ">

                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal" style="
                            min-height:34px;
                            padding:7px 13px;
                            border-radius:8px;
                            color:#475569;
                            font-size:11px;
                            font-weight:600;
                        ">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary" style="
                            min-height:34px;
                            padding:7px 13px;
                            border:1px solid #4f46e5;
                            border-radius:8px;
                            background:#4f46e5;
                            font-size:11px;
                            font-weight:600;
                        ">

                        <i class="fa-solid fa-user-plus me-1" style="font-size:10px;">
                        </i>

                        Create Account

                    </button>

                </div>

            </form>

        </div>
    </div>
</div>