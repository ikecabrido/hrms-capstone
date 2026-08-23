<div
    id="payrollRejectModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="w-full max-w-md bg-white rounded-xl shadow-xl">

        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">

            <div>
                <h3 class="text-sm font-bold text-slate-800">
                    Reject Payroll Request
                </h3>

                <p id="rejectEmployeeName" class="text-[10px] text-slate-400 mt-1"></p>
            </div>

            <button
                type="button"
                onclick="closePayrollRejectModal()"
                class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400">

                <i class="fas fa-times"></i>

            </button>

        </div>

        <form
            action="index.php?url=payroll-reject"
            method="POST">

            <input
                type="hidden"
                name="request_id"
                id="rejectRequestId">

            <div class="p-5">

                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-2">
                    Rejection Reason
                </label>

                <textarea
                    name="rejection_reason"
                    required
                    rows="4"
                    class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs text-slate-600 outline-none resize-none focus:border-red-500"
                    placeholder="Enter the reason for rejecting this request..."></textarea>

            </div>

            <div class="px-5 py-4 border-t border-slate-200 flex justify-end gap-2">

                <button
                    type="button"
                    onclick="closePayrollRejectModal()"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-[10px] font-bold text-slate-600 hover:bg-slate-50">

                    Cancel

                </button>

                <button
                    type="submit"
                    class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold">

                    <i class="fas fa-times mr-1"></i>
                    Reject Request

                </button>

            </div>

        </form>

    </div>

</div>