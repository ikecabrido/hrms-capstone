<!-- =========================================================
     CREATE COURSE MODAL
========================================================= -->

<div class="modal fade" id="createCourseModal" tabindex="-1" aria-labelledby="createCourseModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">

        <div class="modal-content">

            <!-- =====================================================
                 HEADER
            ====================================================== -->

            <div class="modal-header">

                <div>
                    <h5 class="modal-title fw-bold" id="createCourseModalLabel">
                        Create Course
                    </h5>

                    <p class="text-muted small mb-0 mt-1">
                        Create a new learning and development course
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>


            <!-- =====================================================
                 STEP INDICATOR
            ====================================================== -->

            <?php require __DIR__ . '/step-indicator.php'; ?>


            <!-- =====================================================
                 FORM
            ====================================================== -->

            <form id="createCourseForm" method="POST" action="index.php?url=admin-store-course"
                enctype="multipart/form-data">

                <!-- =================================================
                     FORM CONTENT
                ================================================== -->

                <div class="modal-body" style="max-height:65vh; overflow-y:auto;">

                    <!-- STEP 1 -->
                    <?php require __DIR__ . '/create-modal/step-1.php'; ?>


                    <!-- STEP 2 -->
                    <?php require __DIR__ . '/create-modal/step-2.php'; ?>


                    <!-- STEP 3 -->
                    <?php require __DIR__ . '/create-modal/step-3.php'; ?>


                    <!-- STEP 4 -->
                    <?php require __DIR__ . '/create-modal/step-4.php'; ?>

                </div>


                <!-- =================================================
                     FOOTER
                ================================================== -->

                <?php require __DIR__ . '/create-modal/footer.php'; ?>

            </form>

        </div>

    </div>
</div>

<style>
    /* =========================================================
   CREATE COURSE RESPONSIVE
========================================================= */

    @media (max-width: 700px) {

        #createCourseModal {
            padding: 10px !important;
        }

        #createCourseModal>div {
            max-height: 96vh !important;
            border-radius: 16px !important;
        }

        #createCourseModal form>div:first-child {
            padding: 20px !important;
        }

        #createCourseModal form>div:last-child {
            padding: 14px 20px !important;
        }

        .course-form-step [style*="grid-template-columns:repeat(2"] {
            grid-template-columns: 1fr !important;
        }

        .course-form-step [style*="grid-template-columns:1fr 150px"] {
            grid-template-columns: 1fr !important;
        }

        .course-lesson>div {
            grid-template-columns: 1fr !important;
        }

    }

    @media (max-width: 560px) {

        #createCourseModal>div {
            width: 100% !important;
        }

        #createCourseModal [style*="min-width:600px"] {
            min-width: 560px !important;
        }

        #createCourseModal h2 {
            font-size: 18px !important;
        }

    }
</style>