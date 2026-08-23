<div id="rejectLeaveModal"
    class="hidden fixed inset-0 z-[9999] bg-slate-900/50 backdrop-blur-sm items-center justify-center p-4">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">

        <!-- MODAL HEADER -->
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">

            <div>
                <h3 class="text-sm font-bold text-slate-800">
                    Reject Leave Request
                </h3>

                <p class="text-[10px] text-slate-400 mt-1">
                    Provide a reason for rejecting this request.
                </p>
            </div>

            <button type="button" onclick="closeRejectModal()"
                class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200">
                <i class="fas fa-times text-xs"></i>
            </button>

        </div>

        <!-- MODAL BODY -->
        <form id="rejectLeaveForm" action="index.php?url=leave-reject" method="POST">

            <input type="hidden" name="leave_request_id" id="rejectLeaveId">

            <div class="p-5">

                <!-- REQUEST SUMMARY -->
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 mb-4">

                    <div class="text-[9px] uppercase font-bold text-slate-400 mb-2">
                        Request Summary
                    </div>

                    <div class="grid grid-cols-2 gap-3">

                        <div>
                            <div class="text-[9px] text-slate-400">
                                Employee
                            </div>
                            <div id="rejectEmployee" class="text-xs font-bold text-slate-700">
                                —
                            </div>
                        </div>

                        <div>
                            <div class="text-[9px] text-slate-400">
                                Leave Type
                            </div>
                            <div id="rejectLeaveType" class="text-xs font-bold text-slate-700">
                                —
                            </div>
                        </div>

                        <div class="col-span-2">
                            <div class="text-[9px] text-slate-400">
                                Date
                            </div>
                            <div id="rejectDates" class="text-xs font-bold text-slate-700">
                                —
                            </div>
                        </div>

                    </div>

                </div>

                <!-- QUICK REASON -->
                <div class="mb-4">

                    <label class="block mb-1.5 text-[10px] font-bold text-slate-600">
                        Quick Reason
                    </label>

                    <select id="quickRejectReason"
                        class="w-full h-10 px-3 rounded-lg border border-slate-200 text-xs text-slate-600 bg-white outline-none focus:border-red-500">

                        <option value="">
                            Select a standard reason
                        </option>

                        <option value="Peak Business Period">
                            Peak Business Period
                        </option>

                        <option value="Inadequate Team Coverage">
                            Inadequate Team Coverage
                        </option>

                        <option value="Insufficient Leave Balance">
                            Insufficient Leave Balance
                        </option>

                        <option value="Conflicting Work Schedule">
                            Conflicting Work Schedule
                        </option>

                        <option value="Incomplete Leave Documentation">
                            Incomplete Leave Documentation
                        </option>

                        <option value="Leave Dates Not Available">
                            Leave Dates Not Available
                        </option>

                        <option value="Operational Requirements">
                            Operational Requirements
                        </option>

                        <option value="Pending Prior Leave Request">
                            Pending Prior Leave Request
                        </option>

                        <option value="Request Submitted Too Late">
                            Request Submitted Too Late
                        </option>

                    </select>

                </div>

                <!-- REJECTION REASON -->
                <div>

                    <label for="rejectReason" class="block mb-1.5 text-[10px] font-bold text-slate-600">

                        Reason for Rejection
                        <span class="text-red-500">*</span>

                    </label>

                    <textarea name="reject_reason" id="rejectReason" rows="4" required
                        placeholder="Please provide a reason to help the employee understand the decision..."
                        class="w-full px-3 py-2.5 rounded-lg border border-slate-200 text-xs text-slate-700 resize-none outline-none focus:border-red-500"></textarea>

                    <div class="mt-1 text-[9px] text-slate-400">
                        This reason will be saved with the leave request history.
                    </div>

                </div>

            </div>

            <!-- MODAL FOOTER -->
            <div class="px-5 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">

                <button type="button" onclick="closeRejectModal()"
                    class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 text-[10px] font-bold hover:bg-slate-100">

                    Cancel

                </button>

                <button type="submit" id="confirmRejectButton" disabled
                    class="px-4 py-2 rounded-lg bg-red-600 text-white text-[10px] font-bold disabled:opacity-40 disabled:cursor-not-allowed hover:bg-red-700">

                    <i class="fas fa-times mr-1"></i>
                    Confirm Rejection

                </button>

            </div>

        </form>

    </div>

</div>