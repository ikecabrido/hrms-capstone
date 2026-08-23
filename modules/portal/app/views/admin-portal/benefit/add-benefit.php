<!-- ADD BENEFIT MODAL -->
<div class="modal fade" id="addBenefitModal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 rounded-xl shadow-lg">

            <!-- HEADER -->
            <div class="modal-header px-4 py-3 border-b border-slate-200">

                <div>
                    <h5 class="text-sm font-bold text-slate-800 mb-0">
                        Add Benefit / Government Contribution
                    </h5>

                    <p class="text-[10px] text-slate-400 mt-1 mb-0">
                        Add a benefit or government contribution record.
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>

            </div>

            <!-- FORM -->
            <form action="index.php?url=benefits-store" method="POST" enctype="multipart/form-data">

                <div class="modal-body p-4">

                    <!-- EMPLOYEE -->
                    <div class="mb-3">

                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1.5">
                            Employee
                        </label>

                        <select name="employee_id" required
                            class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-xs text-slate-700 outline-none focus:border-blue-500">

                            <option value="">
                                Select Employee
                            </option>

                            <?php if (!empty($employeesList)): ?>

                                <?php foreach ($employeesList as $employee): ?>

                                    <?php
                                    $employeeId = (int) ($employee['id'] ?? 0);

                                    $employeeName = trim(
                                        ($employee['first_name'] ?? '') . ' ' .
                                        ($employee['last_name'] ?? '')
                                    );
                                    ?>

                                    <option value="<?= $employeeId ?>">
                                        <?= htmlspecialchars($employeeName) ?>
                                    </option>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </select>

                    </div>

                    <!-- RECORD TYPE -->
                    <div class="mb-3">

                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1.5">
                            Record Type
                        </label>

                        <select name="record_type" required
                            class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-xs text-slate-700 outline-none focus:border-blue-500">

                            <option value="">
                                Select Type
                            </option>

                            <option value="SSS">
                                SSS
                            </option>

                            <option value="PhilHealth">
                                PhilHealth
                            </option>

                            <option value="Pag-IBIG">
                                Pag-IBIG
                            </option>

                            <option value="BIR Form 2316">
                                BIR Form 2316
                            </option>

                            <option value="HMO">
                                HMO
                            </option>

                            <option value="Rice Allowance">
                                Rice Allowance
                            </option>

                            <option value="Other Benefit">
                                Other Benefit
                            </option>

                        </select>

                    </div>

                    <!-- PERIOD -->
                    <div class="mb-3">

                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1.5">
                            Period
                        </label>

                        <input type="month" name="period" required
                            class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-xs text-slate-700 outline-none focus:border-blue-500">

                    </div>

                    <!-- DESCRIPTION -->
                    <div class="mb-3">

                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1.5">
                            Description
                        </label>

                        <textarea name="description" rows="3" placeholder="Enter description..."
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-xs text-slate-700 outline-none resize-none focus:border-blue-500"></textarea>

                    </div>

                    <!-- FILE -->
                    <div>

                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1.5">
                            Supporting Document
                        </label>

                        <input type="file" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                            class="w-full h-10 px-3 py-2 rounded-lg border border-slate-200 bg-white text-xs text-slate-600 outline-none focus:border-blue-500">

                        <p class="text-[9px] text-slate-400 mt-1">
                            PDF, DOC, DOCX, JPG, JPEG, or PNG.
                        </p>

                    </div>

                </div>

                <!-- FOOTER -->
                <div class="modal-footer px-4 py-3 border-t border-slate-200">

                    <button type="button" data-bs-dismiss="modal"
                        class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-[10px] font-bold hover:bg-slate-50 transition">
                        Cancel
                    </button>

                    <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold transition">

                        <i class="fas fa-plus"></i>
                        Add Benefit

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>