<div class="employee-dashboard">

    <section class="dashboard-welcome" id="dashboardWelcome">

        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label" id="welcomeLabel">
                <i class="fas fa-circle"></i>
                BENEFITS & GOVERNMENT CONTRIBUTIONS
            </span>

            <h1 id="welcomeTitle">
                Employee Benefits & Contributions
            </h1>

            <p id="welcomeDescription">
                Manage employee benefits, government contributions, contribution records,
                supporting documents, and compliance information for SSS, PhilHealth,
                Pag-IBIG, and BIR.
            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration" id="welcomeDecoration">
            <i class="fas fa-hand-holding-usd"></i>
        </div>

    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section id="dashboardWelcome" class="w-full min-h-0 bg-slate-50 p-3 sm:p-4 lg:p-5">

        <!-- HEADER -->
        <div class="mb-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-slate-800">
                        Benefits & Government Contributions
                    </h1>

                    <p class="mt-1 text-xs text-slate-500">
                        Manage employee benefits, government contributions, and compliance records.
                    </p>
                </div>

                <button type="button" data-bs-toggle="modal" data-bs-target="#addBenefitModal"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold transition">

                    <i class="fas fa-plus"></i>
                    Add Benefit

                </button>

            </div>
        </div>


        <?php
        $totalRecords = count($benefitList ?? []);

        $governmentRecords = 0;
        $uploadedDocuments = 0;
        $activeRecords = 0;

        foreach ($benefitList ?? [] as $benefit) {

            $recordType = strtolower(trim($benefit['record_type'] ?? ''));

            if (in_array($recordType, ['sss', 'philhealth', 'pag-ibig', 'pagibig', 'bir'])) {
                $governmentRecords++;
            }

            if (!empty($benefit['file_path'])) {
                $uploadedDocuments++;
            }

            if (!empty($benefit['file_path'])) {
                $activeRecords++;
            }
        }
        ?>


        <!-- SUMMARY CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5">

            <!-- TOTAL RECORDS -->
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-400">
                            Total Records
                        </p>

                        <p class="mt-1 text-xl font-bold text-slate-800">
                            <?= $totalRecords ?>
                        </p>
                    </div>

                    <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-folder-open text-sm"></i>
                    </div>

                </div>

            </div>


            <!-- GOVERNMENT -->
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-400">
                            Government Records
                        </p>

                        <p class="mt-1 text-xl font-bold text-emerald-600">
                            <?= $governmentRecords ?>
                        </p>
                    </div>

                    <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i class="fas fa-landmark text-sm"></i>
                    </div>

                </div>

            </div>


            <!-- DOCUMENTS -->
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-400">
                            Documents
                        </p>

                        <p class="mt-1 text-xl font-bold text-purple-600">
                            <?= $uploadedDocuments ?>
                        </p>
                    </div>

                    <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                        <i class="fas fa-file-alt text-sm"></i>
                    </div>

                </div>

            </div>


            <!-- ACTIVE -->
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-400">
                            Active Records
                        </p>

                        <p class="mt-1 text-xl font-bold text-blue-600">
                            <?= $activeRecords ?>
                        </p>
                    </div>

                    <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-check-circle text-sm"></i>
                    </div>

                </div>

            </div>

        </div>


        <!-- COMPLIANCE STATUS -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm mb-5">

            <div class="px-4 py-3 border-b border-slate-200">

                <h2 class="text-sm font-bold text-slate-800">
                    Government Compliance
                </h2>

                <p class="text-[10px] text-slate-400 mt-0.5">
                    Current government contribution records.
                </p>

            </div>


            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">

                <?php
                $governmentTypes = [
                    'SSS' => 'fa-shield-alt',
                    'PhilHealth' => 'fa-heartbeat',
                    'Pag-IBIG' => 'fa-home',
                    'BIR' => 'fa-file-invoice-dollar'
                ];
                ?>

                <?php foreach ($governmentTypes as $type => $icon): ?>

                    <?php
                    $found = false;

                    foreach ($benefitList ?? [] as $benefit) {
                        if (strtolower(trim($benefit['record_type'] ?? '')) === strtolower($type)) {
                            $found = true;
                            break;
                        }
                    }
                    ?>

                    <div class="px-4 py-4 border-b sm:border-r border-slate-100">

                        <div class="flex items-center justify-between">

                            <div class="flex items-center gap-3">

                                <div class="w-8 h-8 rounded-lg bg-slate-50 text-slate-500 flex items-center justify-center">
                                    <i class="fas <?= $icon ?> text-xs"></i>
                                </div>

                                <div>
                                    <p class="text-xs font-bold text-slate-700">
                                        <?= htmlspecialchars($type) ?>
                                    </p>

                                    <p class="text-[10px] text-slate-400">
                                        Government Contribution
                                    </p>
                                </div>

                            </div>

                            <?php if ($found): ?>

                                <span
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[9px] font-bold">
                                    <i class="fas fa-check-circle"></i>
                                    Current
                                </span>

                            <?php else: ?>

                                <span
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-amber-50 text-amber-700 text-[9px] font-bold">
                                    <i class="fas fa-exclamation-circle"></i>
                                    No Record
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>


        <!-- RECORDS TABLE -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">

            <!-- TABLE HEADER -->
            <div class="px-4 py-3 border-b border-slate-200">

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                    <div>
                        <h2 class="text-sm font-bold text-slate-800">
                            Benefits & Government Records
                        </h2>

                        <p class="text-[10px] text-slate-400 mt-0.5">
                            Manage employee benefit and government contribution documents.
                        </p>
                    </div>

                    <div class="flex gap-2">

                        <input type="text" id="benefitSearch" placeholder="Search..."
                            class="h-9 w-full sm:w-48 px-3 rounded-lg border border-slate-200 text-xs outline-none focus:border-blue-500">

                        <select id="benefitFilter"
                            class="h-9 px-3 rounded-lg border border-slate-200 text-xs text-slate-600 bg-white outline-none focus:border-blue-500">

                            <option value="all">
                                All
                            </option>

                            <option value="sss">
                                SSS
                            </option>

                            <option value="philhealth">
                                PhilHealth
                            </option>

                            <option value="pag-ibig">
                                Pag-IBIG
                            </option>

                            <option value="bir">
                                BIR
                            </option>

                        </select>

                    </div>

                </div>

            </div>


            <!-- TABLE -->
            <div class="w-full overflow-x-auto overflow-y-hidden" style="
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    ">

                <table class="w-full min-w-[1050px] text-left">

                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 whitespace-nowrap">
                                ID
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 whitespace-nowrap">
                                Employee
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 whitespace-nowrap">
                                Record Type
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 whitespace-nowrap">
                                Period
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 whitespace-nowrap">
                                Description
                            </th>

                            <th class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 whitespace-nowrap">
                                Document
                            </th>

                            <th
                                class="px-4 py-3 text-[10px] font-bold uppercase text-slate-400 text-center whitespace-nowrap">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody id="benefitTable">

                        <?php if (!empty($benefitList)): ?>

                            <?php foreach ($benefitList as $benefit): ?>

                                <?php
                                $recordType = trim($benefit['record_type'] ?? '—');
                                $description = trim($benefit['description'] ?? '');
                                $fileName = trim($benefit['file_name'] ?? '');
                                $filePath = trim($benefit['file_path'] ?? '');

                                $employeeId = (int) ($benefit['employee_id'] ?? 0);
                                $employeeName = trim($benefit['employee_name'] ?? 'Unknown Employee');

                                $avatarLetter = strtoupper(substr($employeeName, 0, 1));
                                ?>

                                <tr data-type="<?= htmlspecialchars(strtolower($recordType)) ?>"
                                    class="benefit-row border-b border-slate-100 hover:bg-slate-50 transition">

                                    <!-- ID -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-xs font-bold text-slate-700">
                                            #<?= (int) ($benefit['benefit_id'] ?? 0) ?>
                                        </span>
                                    </td>

                                    <!-- EMPLOYEE -->
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3 min-w-[180px]">

                                            <div
                                                class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold shrink-0">
                                                <?= htmlspecialchars($avatarLetter) ?>
                                            </div>

                                            <div class="min-w-0">
                                                <div class="text-xs font-bold text-slate-800 truncate">
                                                    <?= htmlspecialchars($employeeName) ?>
                                                </div>

                                                <div class="text-[10px] text-slate-400 mt-0.5">
                                                    Employee ID: <?= $employeeId ?>
                                                </div>
                                            </div>

                                        </div>
                                    </td>

                                    <!-- RECORD TYPE -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-[9px] font-bold">
                                            <?= htmlspecialchars($recordType) ?>
                                        </span>
                                    </td>

                                    <!-- PERIOD -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-xs font-semibold text-slate-700">
                                            <?= htmlspecialchars($benefit['period'] ?? '—') ?>
                                        </span>
                                    </td>

                                    <!-- DESCRIPTION -->
                                    <td class="px-4 py-3">
                                        <div class="max-w-[220px]">
                                            <div class="text-xs text-slate-700 truncate">
                                                <?= htmlspecialchars($description ?: '—') ?>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- DOCUMENT -->
                                    <td class="px-4 py-3">
                                        <?php if ($filePath): ?>

                                            <span class="text-xs text-slate-600 truncate block max-w-[220px]">
                                                <?= htmlspecialchars($fileName ?: basename($filePath)) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="text-[10px] text-slate-400">
                                                No document
                                            </span>

                                        <?php endif; ?>
                                    </td>

                                    <!-- ACTION -->
                                    <td class="px-4 py-3 text-center">

                                        <div class="flex items-center justify-end gap-1.5">

                                            <?php if ($filePath): ?>

                                                <?php
                                                $fileUrl = '/hrms-capstone/modules/portal/public/' .
                                                    ltrim(str_replace('\\', '/', $filePath), '/');
                                                ?>

                                                <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold transition shadow-sm whitespace-nowrap">

                                                    <i class="fas fa-file-alt"></i>
                                                    View

                                                </a>

                                            <?php endif; ?>

                                            <button type="button" onclick="openBenefitUploadModal(
                                        <?= (int) $benefit['benefit_id'] ?>,
                                        '<?= htmlspecialchars($employeeName, ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($recordType, ENT_QUOTES) ?>'
                                    )"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold transition shadow-sm whitespace-nowrap">

                                                <i class="fas fa-upload"></i>
                                                Upload

                                            </button>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center">

                                    <div
                                        class="w-12 h-12 mx-auto rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center">
                                        <i class="fas fa-folder-open"></i>
                                    </div>

                                    <p class="mt-3 text-xs font-semibold text-slate-600">
                                        No Records Found
                                    </p>

                                    <p class="mt-1 text-[10px] text-slate-400">
                                        Benefits and government contribution records will appear here.
                                    </p>

                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <!-- PAGINATION -->
            <div
                class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                <p id="benefitPaginationInfo" class="text-[10px] text-slate-400"></p>

                <div id="benefitPagination" class="flex items-center gap-1 flex-wrap"></div>

            </div>

        </div>

    </section>
</div>
<?php require __DIR__ . '/add-benefit.php'; ?>
<?php require __DIR__ . '/upload-file.php'; ?>
<script src="/hrms-capstone/modules/portal/public/js/function/contentBenefitsAdmin.js"></script>
<script>
    document.querySelectorAll('.modal').forEach(modal => {
        document.body.appendChild(modal);
    });
</script>
<style>
    .modal {
        z-index: 1055 !important;
    }

    .modal-backdrop {
        z-index: 1050 !important;
    }
</style>