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
                Training
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
        <div class="max-w-7xl mx-auto mb-8">

            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5">

                <div>
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full
                             bg-blue-50 text-blue-700 text-xs font-semibold mb-3">
                        <i class="fa-solid fa-graduation-cap"></i>
                        Learning & Development
                    </span>

                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">
                        Training, Learning and Development
                    </h1>

                    <p class="mt-2 text-sm sm:text-base text-slate-500 max-w-2xl">
                        Explore available courses, develop new skills, and continue
                        growing professionally.
                    </p>
                </div>

                <!-- Course count -->
                <div class="flex items-center gap-3 text-sm text-slate-500">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white
                            border border-slate-200 shadow-sm">
                        <i class="fa-solid fa-book-open text-blue-600"></i>
                    </div>

                    <div>
                        <p class="font-semibold text-slate-800">
                            <?= count($allTrainingCourses) ?>
                            <?= count($allTrainingCourses) === 1 ? 'Course' : 'Courses' ?>
                        </p>

                        <p class="text-xs text-slate-400">
                            Available for learning
                        </p>
                    </div>
                </div>
                <div style="
    display:flex;
    justify-content:flex-end;
    margin-bottom:18px;
">

                    <button type="button" onclick="openCreateCourseModal()" style="
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            height:44px;
            padding:0 18px;
            border:none;
            border-radius:11px;
            background:#2563eb;
            color:#ffffff;
            font-size:14px;
            font-weight:600;
            cursor:pointer;
            box-shadow:0 4px 10px rgba(37,99,235,.20);
            transition:all .2s ease;
        " onmouseover="
            this.style.background='#1d4ed8';
            this.style.transform='translateY(-1px)';
        " onmouseout="
            this.style.background='#2563eb';
            this.style.transform='translateY(0)';
        ">
                        <i class="fa-solid fa-plus"></i>
                        Create Course
                    </button>

                </div>
            </div>

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

                <div id="courseGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

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

                        <article class="course-card group bg-white rounded-2xl border border-slate-200
                               overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1
                               transition-all duration-300" data-title="<?= htmlspecialchars(strtolower($title)) ?>"
                            data-category="<?= htmlspecialchars(strtolower($category)) ?>">

                            <!-- Thumbnail -->
                            <div class="relative h-48 overflow-hidden bg-slate-100">

                                <?php if (!empty($thumbnailUrl)): ?>

                                    <img src="<?= htmlspecialchars($thumbnailUrl) ?>" alt="<?= htmlspecialchars($title) ?>"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">

                                <?php else: ?>

                                    <div class="w-full h-full flex items-center justify-center bg-slate-100">
                                        <i class="fa-solid fa-book-open text-3xl text-slate-400"></i>
                                    </div>

                                <?php endif; ?>


                                <!-- Overlay -->
                                <div class="absolute inset-0 bg-gradient-to-t
                                        from-black/50 via-transparent to-transparent">
                                </div>


                                <!-- Category -->
                                <div class="absolute top-4 left-4">

                                    <span class="inline-flex items-center px-3 py-1.5
                                             rounded-lg bg-white/95 backdrop-blur
                                             text-xs font-semibold text-slate-700 shadow-sm">
                                        <?= htmlspecialchars($category) ?>
                                    </span>

                                </div>


                                <!-- Status -->
                                <div class="absolute top-4 right-4">

                                    <?php if ($status === 'active'): ?>

                                        <span class="inline-flex items-center gap-1.5
                                                 px-2.5 py-1.5 rounded-lg
                                                 bg-emerald-500 text-white
                                                 text-xs font-semibold shadow-sm">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                            Available
                                        </span>

                                    <?php elseif ($status === 'draft'): ?>

                                        <span class="px-2.5 py-1.5 rounded-lg
                                                 bg-amber-500 text-white
                                                 text-xs font-semibold">
                                            Draft
                                        </span>

                                    <?php else: ?>

                                        <span class="px-2.5 py-1.5 rounded-lg
                                                 bg-slate-600 text-white
                                                 text-xs font-semibold">
                                            Archived
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <!-- Content -->
                            <div class="p-5">

                                <!-- Title -->
                                <h2 class="text-lg font-bold text-slate-900 leading-snug
                                       group-hover:text-blue-600 transition-colors">
                                    <?= htmlspecialchars($title) ?>
                                </h2>


                                <!-- Description -->
                                <p class="mt-2 text-sm text-slate-500 leading-6 line-clamp-3">
                                    <?= htmlspecialchars($description) ?>
                                </p>


                                <!-- Instructor -->
                                <div class="mt-4 flex items-center gap-2">

                                    <div class="w-8 h-8 rounded-full bg-blue-50
                                            flex items-center justify-center">
                                        <i class="fa-solid fa-user text-xs text-blue-600"></i>
                                    </div>

                                    <div>
                                        <p class="text-[11px] text-slate-400 uppercase
                                              tracking-wide">
                                            Course Instructor
                                        </p>

                                        <p class="text-xs font-semibold text-slate-700">
                                            Instructor #<?= htmlspecialchars(
                                                $owner['instructor_id'] ?? $course['instructor_id']
                                            ) ?>
                                        </p>
                                    </div>

                                </div>


                                <!-- Divider -->
                                <div class="my-4 border-t border-slate-100"></div>


                                <!-- Course Information -->
                                <div class="grid grid-cols-2 gap-3">

                                    <div class="flex items-center gap-2">

                                        <div class="w-8 h-8 rounded-lg bg-slate-50
                                                flex items-center justify-center">
                                            <i class="fa-regular fa-calendar text-xs text-slate-500"></i>
                                        </div>

                                        <div>
                                            <p class="text-[10px] text-slate-400">
                                                Starts
                                            </p>

                                            <p class="text-xs font-semibold text-slate-700">
                                                <?= !empty($course['start_date'])
                                                    ? date('M d, Y', strtotime($course['start_date']))
                                                    : 'TBA'
                                                    ?>
                                            </p>
                                        </div>

                                    </div>


                                    <div class="flex items-center gap-2">

                                        <div class="w-8 h-8 rounded-lg bg-slate-50
                                                flex items-center justify-center">
                                            <i class="fa-solid fa-users text-xs text-slate-500"></i>
                                        </div>

                                        <div>
                                            <p class="text-[10px] text-slate-400">
                                                Instructors
                                            </p>

                                            <p class="text-xs font-semibold text-slate-700">
                                                <?= count($instructors) ?>
                                            </p>
                                        </div>

                                    </div>

                                </div>


                                <!-- Skills -->
                                <?php if (!empty($skills)): ?>

                                    <div class="mt-4">

                                        <p class="text-[11px] font-semibold text-slate-400
                                              uppercase tracking-wide mb-2">
                                            Skills you'll develop
                                        </p>

                                        <div class="flex flex-wrap gap-1.5">

                                            <?php
                                            $displaySkills = array_slice($skills, 0, 3);
                                            ?>

                                            <?php foreach ($displaySkills as $skill): ?>

                                                <span class="px-2.5 py-1 rounded-md
                                                       bg-blue-50 text-blue-700
                                                       text-[11px] font-medium">
                                                    Skill #<?= htmlspecialchars($skill['skill_id']) ?>
                                                </span>

                                            <?php endforeach; ?>

                                            <?php if (count($skills) > 3): ?>

                                                <span class="px-2.5 py-1 rounded-md
                                                       bg-slate-100 text-slate-500
                                                       text-[11px] font-medium">
                                                    +<?= count($skills) - 3 ?> more
                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                <?php endif; ?>


                                <!-- Deadline -->
                                <?php if (!empty($course['enrollment_deadline'])): ?>

                                    <div class="mt-4 flex items-center gap-2 text-xs">

                                        <i class="fa-regular fa-clock text-orange-500"></i>

                                        <span class="text-slate-400">
                                            Enrollment deadline:
                                        </span>

                                        <span class="font-semibold text-slate-700">
                                            <?= date(
                                                'M d, Y',
                                                strtotime($course['enrollment_deadline'])
                                            ) ?>
                                        </span>

                                    </div>

                                <?php endif; ?>


                                <!-- Action -->
                                <div class="mt-5">

                                    <?php if ($status === 'active'): ?>

                                        <a href="?route=training-show&id=<?= $courseId ?>" class="w-full h-11 inline-flex items-center
                                               justify-center gap-2 rounded-xl
                                               bg-blue-600 text-white text-sm
                                               font-semibold
                                               hover:bg-blue-700
                                               active:scale-[.98]
                                               transition-all">
                                            View Course

                                            <i class="fa-solid fa-arrow-right text-xs
                                                  group-hover:translate-x-1
                                                  transition-transform"></i>
                                        </a>

                                    <?php else: ?>

                                        <button type="button" disabled class="w-full h-11 rounded-xl bg-slate-100
                                               text-slate-400 text-sm font-semibold
                                               cursor-not-allowed">
                                            Course Unavailable
                                        </button>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>


                <!-- No Search Results -->
                <div id="noCoursesFound" class="hidden py-16 text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100
                            flex items-center justify-center mb-4">
                        <i class="fa-solid fa-magnifying-glass text-xl text-slate-400"></i>
                    </div>

                    <h3 class="text-lg font-semibold text-slate-800">
                        No courses found
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Try another search term or category.
                    </p>
                </div>

            <?php else: ?>

                <!-- Empty State -->
                <div class="bg-white rounded-2xl border border-slate-200
                        py-20 px-6 text-center">

                    <div class="w-20 h-20 mx-auto rounded-2xl bg-blue-50
                            flex items-center justify-center mb-5">
                        <i class="fa-solid fa-graduation-cap text-3xl text-blue-600"></i>
                    </div>

                    <h2 class="text-xl font-bold text-slate-900">
                        No courses available
                    </h2>

                    <p class="mt-2 text-sm text-slate-500 max-w-md mx-auto">
                        There are currently no training courses available.
                        Please check again later for new learning opportunities.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </section>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {

        const searchInput = document.getElementById('courseSearch');
        const categorySelect = document.getElementById('courseCategory');
        const cards = document.querySelectorAll('.course-card');
        const noResults = document.getElementById('noCoursesFound');

        function filterCourses() {

            const search = (searchInput?.value || '').toLowerCase().trim();
            const category = (categorySelect?.value || '').toLowerCase();

            let visible = 0;

            cards.forEach(card => {

                const title = card.dataset.title || '';
                const cardCategory = card.dataset.category || '';

                const matchesSearch =
                    !search || title.includes(search);

                const matchesCategory =
                    !category || cardCategory === category;

                if (matchesSearch && matchesCategory) {
                    card.classList.remove('hidden');
                    visible++;
                } else {
                    card.classList.add('hidden');
                }

            });

            if (noResults) {
                noResults.classList.toggle('hidden', visible !== 0);
            }
        }

        searchInput?.addEventListener('input', filterCourses);
        categorySelect?.addEventListener('change', filterCourses);

    });
</script>
<?php require __DIR__ . '/create-course.php'; ?>

<div id="courseValidationMessage" style="
        display:none;
        align-items:center;
        gap:8px;
        padding:9px 12px;
        border:1px solid #fecaca;
        border-radius:9px;
        background:#fef2f2;
        color:#b91c1c;
        font-size:12px;
        font-weight:500;
    ">
    <i class="fa-solid fa-circle-exclamation"></i>
    <span>Please complete all required fields before creating the course.</span>
</div>>