<div class="modal fade" id="benefitUploadModal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 rounded-3xl shadow-lg">

            <form action="index.php?url=benefit-upload" method="POST" enctype="multipart/form-data">

                <div class="modal-header border-0 px-4 pt-4">

                    <div>
                        <h5 class="text-sm font-bold text-slate-800 mb-1">
                            Upload Benefit Document
                        </h5>

                        <p class="text-[10px] text-slate-400 mb-0">
                            Upload a document for the selected employee.
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                </div>

                <div class="modal-body px-4">

                    <input type="hidden" name="benefit_id" id="uploadBenefitId">

                    <div class="mb-3">

                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">
                            Employee
                        </label>

                        <input type="text" id="uploadEmployeeName" class="form-control text-xs bg-slate-50" readonly>

                    </div>

                    <div class="mb-3">

                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">
                            Record Type
                        </label>

                        <input type="text" id="uploadRecordType" class="form-control text-xs bg-slate-50" readonly>

                    </div>

                    <div class="mb-3">

                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">
                            Select File
                        </label>

                        <input type="file" name="document" class="form-control text-xs"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>

                        <div class="text-[9px] text-slate-400 mt-1">
                            PDF, DOC, DOCX, JPG, JPEG, PNG
                        </div>

                    </div>

                </div>

                <div class="modal-footer border-0 px-4 pb-4">

                    <button type="button"
                        class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-bold"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                        class="px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold">

                        <i class="fas fa-upload me-1"></i>
                        Upload File

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>