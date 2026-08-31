<div class="employee-dashboard">

    <section class="dashboard-welcome" id="trainingWelcome">
        <!-- Animated background -->
        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>
        <div class="welcome-content">
            <span class="welcome-label">
                <i class="fas fa-circle"></i>
                TRAINING & DEVELOPMENT
            </span>
            <h1 class="welcome-title">
                Course Management
            </h1>
            <p class="welcome-description">
                View available training programs, track your training requests,
                and monitor your professional development progress.
            </p>
            <div class="welcome-line"></div>
        </div>
        <!-- Training icon -->
        <div class="welcome-decoration">
            <i class="fas fa-graduation-cap"></i>
        </div>
    </section>

    <?php require __DIR__ . '/../../partials/notification.php'; ?>

    <section class="w-full min-h-screen bg-slate-50 p-4 sm:p-6 lg:p-8">

        <!-- Header -->
        <div style="
    display:flex;
    justify-content:flex-end;
    align-items:center;
    gap:10px;
    margin-bottom:18px;
">

            <!-- VIEW TRAINING REQUEST -->
            <a href="index.php?url=view-training-request" class="btn btn-primary d-inline-flex align-items-center gap-2"
                style="
            height:44px;
            padding:0 18px;
            border-radius:11px;
            font-size:14px;
            font-weight:600;
        ">

                <i class="fa-solid fa-plus"></i>
                View Training Request
            </a>

            <!-- CREATE COURSE -->
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal"
                data-bs-target="#createCourseModal" style="
            height:44px;
            padding:0 18px;
            border-radius:11px;
            font-size:14px;
            font-weight:600;
        ">

                <i class="fa-solid fa-plus"></i>
                Create Course

            </button>

        </div>

        <?php require __DIR__ . '/../../partials/notification.php'; ?>

        <!-- Search / Filter Bar -->
        <div style="
    max-width:1280px;
    margin:0 auto 32px;
">

            <div style="
        display:flex;
        flex-direction:column;
        gap:12px;
        padding:12px;
        background:#ffffff;
        border:1px solid #e2e8f0;
        border-radius:16px;
        box-shadow:0 4px 12px rgba(15,23,42,.05);
    ">

                <!-- Search -->
                <div style="
            position:relative;
            flex:1;
            width:100%;
        ">

                    <i class="fa-solid fa-magnifying-glass" style="
                position:absolute;
                left:16px;
                top:50%;
                transform:translateY(-50%);
                color:#94a3b8;
                font-size:14px;
                pointer-events:none;
                z-index:2;
            "></i>

                    <input type="text" id="courseSearch" placeholder="Search courses..." style="
                    box-sizing:border-box;
                    width:100%;
                    height:46px;
                    padding:0 16px 0 44px;
                    border:1px solid #e2e8f0;
                    border-radius:12px;
                    background:#f8fafc;
                    color:#334155;
                    font-size:14px;
                    font-weight:500;
                    outline:none;
                    transition:all .2s ease;
                " onfocus="
                    this.style.background='#ffffff';
                    this.style.borderColor='#60a5fa';
                    this.style.boxShadow='0 0 0 3px rgba(59,130,246,.10)';
                " onblur="
                    this.style.background='#f8fafc';
                    this.style.borderColor='#e2e8f0';
                    this.style.boxShadow='none';
                ">

                </div>


                <!-- Category -->
                <div style="
            position:relative;
            width:100%;
        ">

                    <i class="fa-solid fa-layer-group" style="
                position:absolute;
                left:16px;
                top:50%;
                transform:translateY(-50%);
                color:#94a3b8;
                font-size:13px;
                pointer-events:none;
                z-index:2;
            "></i>

                    <select id="courseCategory" style="
                    box-sizing:border-box;
                    appearance:none;
                    width:100%;
                    height:46px;
                    padding:0 42px 0 42px;
                    border:1px solid #e2e8f0;
                    border-radius:12px;
                    background:#f8fafc;
                    color:#334155;
                    font-size:14px;
                    font-weight:500;
                    outline:none;
                    cursor:pointer;
                    transition:all .2s ease;
                " onfocus="
                    this.style.background='#ffffff';
                    this.style.borderColor='#60a5fa';
                    this.style.boxShadow='0 0 0 3px rgba(59,130,246,.10)';
                " onblur="
                    this.style.background='#f8fafc';
                    this.style.borderColor='#e2e8f0';
                    this.style.boxShadow='none';
                ">

                        <option value="">All Categories</option>

                        <?php
                        $categories = [];

                        foreach ($allTrainingCourses as $course) {
                            if (!empty($course['category'])) {
                                $categories[] = $course['category'];
                            }
                        }

                        $categories = array_unique($categories);
                        sort($categories);

                        foreach ($categories as $category):
                            ?>

                            <option value="<?= htmlspecialchars(strtolower($category)) ?>">
                                <?= htmlspecialchars($category) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>


                    <i class="fa-solid fa-chevron-down" style="
                position:absolute;
                right:16px;
                top:50%;
                transform:translateY(-50%);
                color:#94a3b8;
                font-size:10px;
                pointer-events:none;
            "></i>

                </div>

            </div>

        </div>

        <!-- Courses -->
        <div class="max-w-7xl mx-auto">

            <?php if (!empty($allTrainingCourses)): ?>

                <div id="courseGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">

                    <?php foreach ($allTrainingCourses as $course): ?>

                        <?php
                        $courseId = (int) $course['id'];
                        $title = $course['title'] ?? 'Untitled Course';
                        $description = $course['description'] ?? '';
                        $category = $course['category'] ?? 'General';
                        $status = $course['status'] ?? 'draft';
                        $thumbnail = $course['thumbnail_path'] ?? '';

                        if (!empty($thumbnail)) {
                            $thumbnailUrl = '/hrms-capstone/modules/portal/public/' . ltrim($thumbnail, '/');
                        } else {
                            $thumbnailUrl = '';
                        }

                        $instructors = $course['instructors'] ?? [];
                        $skills = $course['skills'] ?? [];
                        $versions = $course['versions'] ?? [];

                        $owner = null;

                        foreach ($instructors as $instructor) {
                            if (($instructor['role'] ?? '') === 'owner') {
                                $owner = $instructor;
                                break;
                            }
                        }

                        $versionCount = count($versions);
                        ?>

                        <article class="course-card group flex flex-col bg-white rounded-2xl
                           border border-slate-200 overflow-hidden
                           shadow-sm hover:shadow-lg hover:-translate-y-1
                           transition-all duration-300" data-title="<?= htmlspecialchars(strtolower($title)) ?>"
                            data-category="<?= htmlspecialchars(strtolower($category)) ?>">

                            <!-- ================================================= -->
                            <!-- THUMBNAIL -->
                            <!-- ================================================= -->

                            <div class="relative h-52 overflow-hidden bg-slate-100">

                                <?php if (!empty($thumbnailUrl)): ?>

                                    <img src="<?= htmlspecialchars($thumbnailUrl) ?>" alt="<?= htmlspecialchars($title) ?>" class="w-full h-full object-cover
                                       group-hover:scale-105
                                       transition-transform duration-500">

                                <?php else: ?>

                                    <div class="w-full h-full flex items-center justify-center
                                        bg-gradient-to-br from-slate-100 to-slate-200">

                                        <div class="w-16 h-16 rounded-2xl bg-white
                                            flex items-center justify-center shadow-sm">

                                            <i class="fa-solid fa-book-open text-2xl text-slate-400"></i>

                                        </div>

                                    </div>

                                <?php endif; ?>


                                <!-- Bottom Gradient -->

                                <div class="absolute inset-x-0 bottom-0 h-24
                                    bg-gradient-to-t from-black/50 to-transparent">
                                </div>


                                <!-- Category -->

                                <div class="absolute top-4 left-4">

                                    <span class="inline-flex items-center px-3 py-1.5
                                       rounded-lg bg-white/95 backdrop-blur
                                       text-xs font-semibold text-slate-700
                                       shadow-sm">

                                        <i class="fa-solid fa-layer-group mr-1.5 text-blue-600"></i>

                                        <?= htmlspecialchars($category) ?>

                                    </span>

                                </div>


                                <!-- Status -->

                                <div class="absolute top-4 right-4">

                                    <?php if ($status === 'active'): ?>

                                        <span class="inline-flex items-center gap-1.5
                                           px-3 py-1.5 rounded-lg
                                           bg-emerald-500 text-white
                                           text-xs font-semibold shadow-sm">

                                            <span class="w-1.5 h-1.5 rounded-full bg-white"></span>

                                            Available

                                        </span>

                                    <?php elseif ($status === 'draft'): ?>

                                        <span class="inline-flex items-center
                                           px-3 py-1.5 rounded-lg
                                           bg-amber-500 text-white
                                           text-xs font-semibold shadow-sm">

                                            <i class="fa-solid fa-pen-ruler mr-1.5"></i>

                                            Draft

                                        </span>

                                    <?php else: ?>

                                        <span class="inline-flex items-center
                                           px-3 py-1.5 rounded-lg
                                           bg-slate-600 text-white
                                           text-xs font-semibold shadow-sm">

                                            <i class="fa-solid fa-box-archive mr-1.5"></i>

                                            Archived

                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <!-- ================================================= -->
                            <!-- ADMIN ACTION BAR -->
                            <!-- ================================================= -->

                            <div class="px-4 pt-4">

                                <div class="flex items-center gap-2 p-1.5
        rounded-xl bg-slate-50
        border border-slate-100">

                                    <!-- EDIT -->
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#editCourseModal"
                                        data-course-id="<?= (int) $course['id'] ?>" class="flex-1 inline-flex items-center justify-center
            gap-1.5 h-9 px-3 rounded-lg
            bg-white border border-slate-200
            text-slate-700 text-xs font-semibold
            hover:border-blue-300 hover:text-blue-600
            hover:bg-blue-50 transition-all">

                                        <i class="fa-solid fa-pen-to-square"></i>
                                        Edit
                                    </button>

                                    <!-- MANAGE CONTENT -->
                                    <form action="index.php?url=manage-course-module" method="POST">
                                        <input type="hidden" value="<?= (int) $course['id'] ?>" name="course_id">
                                        <button type="submit" class="flex-1 inline-flex items-center justify-center
                                            gap-1.5 h-9 px-3 rounded-lg
                                            bg-blue-50 border border-blue-100
                                            text-blue-700 text-xs font-semibold
                                            hover:bg-blue-100 hover:border-blue-200
                                            transition-all">
                                            <i class="fa-solid fa-layer-group"></i>
                                            Manage
                                        </button>
                                    </form>

                                    <!-- STATUS -->
                                    <?php if (($course['status'] ?? 'draft') === 'draft'): ?>

                                        <form method="POST" action="index.php?url=admin-course-toggle-status" class="flex-1 m-0">

                                            <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">

                                            <button type="submit" class="w-full inline-flex items-center justify-center
                    gap-1.5 h-9 px-3 rounded-lg
                    bg-emerald-50 border border-emerald-100
                    text-emerald-700 text-xs font-semibold
                    hover:bg-emerald-100 transition-all">

                                                <i class="fa-solid fa-circle-check"></i>
                                                Available
                                            </button>

                                        </form>

                                    <?php elseif (($course['status'] ?? '') === 'active'): ?>

                                        <form method="POST" action="index.php?url=admin-course-toggle-status" class="flex-1 m-0">

                                            <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">

                                            <button type="submit" class="w-full inline-flex items-center justify-center
                    gap-1.5 h-9 px-3 rounded-lg
                    bg-amber-50 border border-amber-100
                    text-amber-700 text-xs font-semibold
                    hover:bg-amber-100 transition-all">

                                                <i class="fa-solid fa-circle-pause"></i>
                                                Draft
                                            </button>

                                        </form>

                                    <?php endif; ?>


                                    <!-- DELETE -->
                                    <form method="POST" action="index.php?url=admin-delete-course" class="m-0"
                                        onsubmit="return confirm('Are you sure you want to delete this course? This action cannot be undone.');">

                                        <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">

                                        <button type="submit" title="Delete course" class="w-9 h-9 inline-flex items-center justify-center
                rounded-lg
                bg-white border border-red-100
                text-red-500
                hover:bg-red-50 hover:border-red-200
                transition-all">

                                            <i class="fa-solid fa-trash text-xs"></i>

                                        </button>

                                    </form>

                                </div>

                            </div>

                            <!-- ================================================= -->
                            <!-- CONTENT -->
                            <!-- ================================================= -->

                            <div class="flex flex-col flex-1 p-4">

                                <!-- TITLE -->
                                <div>
                                    <h2 class="text-base font-bold text-slate-900
               leading-snug line-clamp-2
               group-hover:text-blue-600
               transition-colors">
                                        <?= htmlspecialchars($title) ?>
                                    </h2>

                                    <p class="mt-1.5 text-xs text-slate-500
              leading-5 line-clamp-2">
                                        <?= htmlspecialchars($description) ?>
                                    </p>
                                </div>

                                <!-- DIVIDER -->

                                <div class="my-3 border-t border-slate-100"></div>


                                <!-- ================================================= -->
                                <!-- COURSE INFORMATION -->
                                <!-- ================================================= -->

                                <div class="grid grid-cols-2 gap-2.5">

                                    <!-- START DATE -->

                                    <div class="flex items-center gap-2
                rounded-lg bg-slate-50
                border border-slate-100
                px-2.5 py-2">

                                        <div class="w-7 h-7 shrink-0 rounded-md
                    bg-white
                    flex items-center justify-center">

                                            <i class="fa-regular fa-calendar
                      text-[10px] text-slate-500"></i>

                                        </div>

                                        <div class="min-w-0">

                                            <p class="text-[9px] text-slate-400">
                                                Starts
                                            </p>

                                            <p class="text-[11px] font-semibold
                      text-slate-700 truncate">

                                                <?= !empty($course['start_date'])
                                                    ? date('M d, Y', strtotime($course['start_date']))
                                                    : 'TBA'
                                                    ?>

                                            </p>

                                        </div>

                                    </div>


                                    <!-- INSTRUCTORS -->

                                    <div class="flex items-center gap-2
            rounded-lg bg-slate-50
            border border-slate-100
            px-2.5 py-2">

                                        <div class="w-7 h-7 shrink-0 rounded-md
                bg-white
                flex items-center justify-center">

                                            <i class="fa-solid fa-users
                  text-[10px] text-slate-500"></i>

                                        </div>

                                        <div class="min-w-0">

                                            <p class="text-[9px] text-slate-400">
                                                Instructor
                                            </p>

                                            <p class="text-xs font-semibold text-slate-700 truncate">
                                                <?= htmlspecialchars(
                                                    $course['instructor_name']
                                                    ?? 'No instructor assigned',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </p>

                                        </div>

                                    </div>

                                </div>


                                <!-- ================================================= -->
                                <!-- SKILLS -->
                                <!-- ================================================= -->

                                <?php if (!empty($skills)): ?>

                                    <div class="mt-3">

                                        <p class="mb-1.5 text-[9px] font-bold
                  text-slate-400
                  uppercase tracking-wider">

                                            Skills you'll develop

                                        </p>

                                        <div class="flex flex-wrap gap-1">

                                            <?php
                                            $displaySkills = array_slice($skills, 0, 3);
                                            ?>

                                            <?php foreach ($displaySkills as $skill): ?>

                                                <span class="inline-flex items-center
                             px-2 py-1 rounded-md
                             bg-blue-50
                             text-blue-700
                             text-[10px]
                             font-medium">

                                                    <?= htmlspecialchars($skill['skill_name']) ?>

                                                </span>

                                            <?php endforeach; ?>


                                            <?php if (count($skills) > 3): ?>

                                                <span class="inline-flex items-center
                             px-2 py-1 rounded-md
                             bg-slate-100
                             text-slate-500
                             text-[10px]
                             font-medium">

                                                    +<?= count($skills) - 3 ?> more

                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                <?php endif; ?>


                                <!-- ================================================= -->
                                <!-- DEADLINE -->
                                <!-- ================================================= -->

                                <?php if (!empty($course['enrollment_deadline'])): ?>

                                    <div class="mt-2.5 flex items-center gap-2
                px-2.5 py-2 rounded-lg
                bg-orange-50
                border border-orange-100">

                                        <i class="fa-regular fa-clock
                  text-orange-500 text-[10px]"></i>

                                        <span class="text-[10px] text-orange-700">
                                            Enrollment deadline:
                                        </span>

                                        <span class="text-[10px]
                     font-bold text-orange-800">

                                            <?= date(
                                                'M d, Y',
                                                strtotime($course['enrollment_deadline'])
                                            ) ?>

                                        </span>

                                    </div>

                                <?php endif; ?>


                                <!-- ================================================= -->
                                <!-- BOTTOM ACTION -->
                                <!-- ================================================= -->

                                <div class="mt-auto pt-5">

                                    <?php if ($status === 'active'): ?>

                                        <button type="button" data-bs-toggle="modal" data-bs-target="#viewCourseModal"
                                            data-course-id="<?= (int) $courseId ?>" class="w-full h-11
                                           inline-flex items-center
                                           justify-center gap-2
                                           rounded-xl
                                           bg-blue-600 text-white
                                           text-sm font-semibold
                                           hover:bg-blue-700
                                           hover:shadow-md
                                           active:scale-[.98]
                                           transition-all">

                                            View Course

                                            <i class="fa-solid fa-arrow-right text-xs
                                               group-hover:translate-x-1
                                               transition-transform"></i>

                                        </button>

                                    <?php else: ?>

                                        <button type="button" disabled class="w-full h-11
                                           inline-flex items-center
                                           justify-center gap-2
                                           rounded-xl
                                           bg-slate-100
                                           border border-slate-200
                                           text-slate-400
                                           text-sm font-semibold
                                           cursor-not-allowed">

                                            <i class="fa-solid fa-lock text-xs"></i>

                                            Course Unavailable

                                        </button>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>


                <!-- ========================================================= -->
                <!-- NO SEARCH RESULTS -->
                <!-- ========================================================= -->

                <div id="noCoursesFound" class="hidden py-16 text-center">

                    <div class="w-16 h-16 mx-auto rounded-2xl
                       bg-slate-100
                       flex items-center justify-center mb-4">

                        <i class="fa-solid fa-magnifying-glass
                           text-xl text-slate-400"></i>

                    </div>

                    <h3 class="text-lg font-semibold text-slate-800">

                        No courses found

                    </h3>

                    <p class="mt-1 text-sm text-slate-500">

                        Try another search term or category.

                    </p>

                </div>


            <?php else: ?>


                <!-- ========================================================= -->
                <!-- EMPTY STATE -->
                <!-- ========================================================= -->

                <div class="bg-white rounded-2xl
                   border border-slate-200
                   py-20 px-6 text-center
                   shadow-sm">

                    <div class="w-20 h-20 mx-auto rounded-2xl
                       bg-blue-50
                       flex items-center justify-center mb-5">

                        <i class="fa-solid fa-graduation-cap
                           text-3xl text-blue-600"></i>

                    </div>

                    <h2 class="text-xl font-bold text-slate-900">

                        No courses available

                    </h2>

                    <p class="mt-2 text-sm text-slate-500
                       max-w-md mx-auto">

                        There are currently no training courses available.
                        Please check again later for new learning opportunities.

                    </p>

                </div>

            <?php endif; ?>

        </div>

    </section>
</div>

<?php require __DIR__ . '/create-course.php'; ?>
<?php require __DIR__ . '/edit-course.php'; ?>
<?php require __DIR__ . '/view-course.php'; ?>

<script src="/hrms-capstone/modules/portal/public/js/function/contentLearningAdmin.js"></script>

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