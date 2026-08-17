<div
    id="payrollUploadModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="w-full max-w-md bg-white rounded-xl shadow-xl">

        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">

            <div>
                <h3 class="text-sm font-bold text-slate-800">
                    Upload Payroll Document
                </h3>

                <p id="uploadEmployeeName" class="text-[10px] text-slate-400 mt-1"></p>
            </div>

            <button
                type="button"
                onclick="closePayrollUploadModal()"
                class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400">

                <i class="fas fa-times"></i>

            </button>

        </div>

        <form
            action="index.php?url=payroll-approve-upload"
            method="POST"
            enctype="multipart/form-data">

            <input
                type="hidden"
                name="request_id"
                id="uploadRequestId">

            <div class="p-5">

                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-2">
                    Payroll Document
                </label>

                <input
                    type="file"
                    name="document"
                    required
                    accept=".pdf,.doc,.docx,.xls,.xlsx"
                    class="w-full h-10 px-3 py-2 rounded-lg border border-slate-200 text-xs text-slate-600 bg-white">

                <p class="text-[10px] text-slate-400 mt-2">
                    Upload the requested payroll document.
                </p>

            </div>

            <div class="px-5 py-4 border-t border-slate-200 flex justify-end gap-2">

                <button
                    type="button"
                    onclick="closePayrollUploadModal()"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-[10px] font-bold text-slate-600 hover:bg-slate-50">

                    Cancel

                </button>

                <button
                    type="submit"
                    class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold">

                    <i class="fas fa-upload mr-1"></i>
                    Upload & Approve

                </button>

            </div>

        </form>

    </div>

</div>