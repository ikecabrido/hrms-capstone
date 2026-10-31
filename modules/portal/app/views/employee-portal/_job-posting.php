<style>
    .bcp-jobs {
        --bcp-primary: #0866e9;
        --bcp-blue: #0754c7;
        --bcp-dark: #10265c;
        --bcp-light: #eaf3ff;
        --bcp-text: #243b70;

        position: relative;
        margin-bottom: 25px;
        overflow: hidden;
        background: #f8fbff;
        border: 1px solid #d7e7ff;
        border-radius: 18px;
        box-shadow: 0 12px 35px rgba(37, 99, 235, .13);
        color: var(--bcp-text);
    }

    /* BCP BRAND HEADER */
    .bcp-jobs-header {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        min-height: 145px;
        padding: 25px 35px;
        overflow: hidden;
        background: linear-gradient(115deg, #ffffff 0%, #e5f1ff 52%, #0754c7 100%);
        border-bottom: 7px solid #176ce5;
    }

    .bcp-jobs-header::after {
        content: "";
        position: absolute;
        top: -100px;
        right: 15%;
        width: 300px;
        height: 350px;
        background: rgba(255,255,255,.23);
        transform: rotate(35deg);
        pointer-events: none;
    }

    .bcp-brand {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 20px;
        min-width: 0;
    }

    .bcp-brand img {
        width: 105px;
        height: 95px;
        object-fit: contain;
        flex-shrink: 0;
    }

    .bcp-brand-divider {
        width: 2px;
        height: 65px;
        background: #1d4ed8;
        opacity: .6;
    }

    .bcp-brand-name {
        color: #103477;
        font-size: clamp(20px, 3vw,  thirtypx);
        font-weight: 900;
        line-height: 1.2;
    }

    .bcp-brand-name small {
        display: block;
        margin-top: 7px;
        color: #0754c7;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 2px;
    }

    .bcp-brand-motto {
        position: relative;
        z-index: 2;
        max-width: 180px;
        color: white;
        font-size: 17px;
        font-weight: 600;
        font-style: italic;
        line-height: 1.5;
        text-align: right;
    }

    .bcp-brand-motto::after {
        content: "";
        display: block;
        width: 45px;
        height: 3px;
        margin: 10px 0 0 auto;
        background: white;
        border-radius: 5px;
    }

    /* POSTING CONTAINER */
    .bcp-job-inner {
        position: relative;
        z-index: 2;
        margin: -1px 15px 0;
        overflow: hidden;
        background: #fff;
        border: 1px solid #e5efff;
        border-radius: 16px;
        box-shadow: 0 5px 20px rgba(30, 64, 175, .07);
    }

    .bcp-job-slide {
        display: none;
        animation: bcpJobFade .5s ease;
    }

    .bcp-job-slide.active {
        display: block;
    }

    @keyframes bcpJobFade {
        from {
            opacity: 0;
            transform: translateY(12px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .bcp-job-main {
        padding: 27px 30px 25px;
    }

    /* COMPANY INFORMATION */
    .bcp-company {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 22px;
    }

    .bcp-company-logo {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100px;
        height: 75px;
        flex-shrink: 0;
        overflow: hidden;
        background: #f5f9ff;
        border: 1px solid #e5efff;
        border-radius: 12px;
    }

    .bcp-company-logo img {
        width: 85%;
        height: 85%;
        object-fit: contain;
    }

    .bcp-company-name {
        color: var(--bcp-dark);
        font-size: 20px;
        font-weight: 850;
        line-height: 1.4;
    }

    .bcp-company-subtitle {
        margin-top: 5px;
        color: #5373ae;
        font-size: 13px;
        letter-spacing: .3px;
    }

    /* JOB CONTENT */
    .bcp-job-grid {
        display: grid;
        grid-template-columns: 1.2fr .8fr;
        gap: 35px;
    }

    .bcp-hiring-title {
        margin: 0 0 22px;
        color: var(--bcp-primary);
        font-size: clamp(37px, 5vw, 62px);
        font-weight: 900;
        line-height: 1;
        letter-spacing: -2px;
    }

    .bcp-hiring-title span {
        color: var(--bcp-dark);
    }

    .bcp-job-group {
        margin-bottom: 20px;
    }

    .bcp-job-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
        color: var(--bcp-primary);
        font-size: 19px;
        font-weight: 800;
    }

    .bcp-job-heading i {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        background: #e5f0ff;
        border-radius: 50%;
        font-size: 18px;
    }

    .bcp-job-list {
        margin: 0;
        padding: 0 0 0 54px;
        list-style: none;
    }

    .bcp-job-list li {
        position: relative;
        margin-bottom: 6px;
        color: #304875;
        font-size: 13px;
        line-height: 1.6;
    }

    .bcp-job-list li::before {
        content: "\f00c";
        position: absolute;
        top: 3px;
        left: -24px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 15px;
        height: 15px;
        background: #0866e9;
        color: white;
        border-radius: 50%;
        font-family: "Font Awesome 5 Free";
        font-size: 8px;
        font-weight: 900;
    }
/* OPEN POSITIONS */
.bcp-positions {
    align-self: stretch;
    overflow: hidden;
    padding: 22px;
    background: linear-gradient(145deg, #0866e9, #0754c7);
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(8, 102, 233, .15);
}

.bcp-positions-heading {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    color: white;
    font-size: 22px;
    font-weight: 850;
}

.bcp-positions-heading i {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 58px;
    height: 58px;
    background: rgba(255,255,255,.2);
    border-radius: 50%;
    font-size: 24px;
}

.bcp-position-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.bcp-position-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 15px;
    background: #f1f7ff;
    border: 1px solid rgba(255,255,255,.5);
    border-radius: 11px;
    color: #10265c;
    transition: all .25s ease;
}

.bcp-position-item:hover {
    background: #ffffff;
    transform: translateY(-2px);
}

.bcp-position-name {
    font-size: 14px;
    font-weight: 700;
    line-height: 1.5;
}

.bcp-position-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    min-width: 75px;
    padding: 6px 11px;
    background: #dceaff;
    color: #0754c7;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
}

.bcp-no-position {
    padding: 13px;
    background: rgba(255,255,255,.15);
    color: white;
    border-radius: 8px;
    text-align: center;
    font-size: 13px;
}

@media (max-width: 420px) {
    .bcp-position-item {
        padding: 11px 12px;
    }

    .bcp-position-name {
        font-size: 13px;
    }

    .bcp-position-count {
        min-width: 65px;
        padding: 5px 9px;
        font-size: 11px;
    }
}

    /* CONTACT FOOTER */
    .bcp-job-footer {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        align-items: center;
        gap: 20px;
        margin: 0 20px 20px;
        padding: 20px 25px;
        background: linear-gradient(110deg, #edf5ff, #e1efff);
        border: 1px solid #d9e9ff;
        border-radius: 13px;
    }

    .bcp-contact {
        display: flex;
        align-items: center;
        gap: 15px;
        min-width: 0;
    }

    .bcp-contact-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 55px;
        height: 55px;
        flex-shrink: 0;
        background: linear-gradient(135deg, #1680ff, #0754c7);
        color: white;
        border-radius: 50%;
        font-size: 21px;
    }

    .bcp-contact-title {
        display: block;
        margin-bottom: 7px;
        color: #0866e9;
        font-size: 16px;
        font-weight: 850;
    }

    .bcp-contact-info {
        display: block;
        margin-top: 5px;
        color: #304875;
        font-size: 12px;
        overflow-wrap: anywhere;
    }

    .bcp-join-message {
        color: #0866e9;
        font-size: 25px;
        font-weight: 700;
        font-style: italic;
        text-align: center;
        transform: rotate(-7deg);
    }

    /* DOT PAGINATION */
    .bcp-job-pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 11px;
        padding: 20px;
        background: white;
        border-top: 1px solid #edf2fa;
    }

    .bcp-job-dot {
        width: 11px;
        height: 11px;
        padding: 0;
        border: none;
        border-radius: 20px;
        background: #bfd5f5;
        cursor: pointer;
        transition: all .3s ease;
    }

    .bcp-job-dot.active {
        width: 25px;
        background: #0754c7;
    }

    .bcp-job-dot:hover {
        background: #0866e9;
    }

    /* EMPTY STATE */
    .bcp-job-empty {
        padding: 55px 20px;
        text-align: center;
        color: #64748b;
    }

    .bcp-job-empty i {
        color: #0866e9;
        font-size: 42px;
    }

    /* RESPONSIVE */
    @media (max-width: 750px) {
        .bcp-jobs-header {
            min-height: 120px;
            padding: 20px;
        }

        .bcp-brand {
            gap: 10px;
        }

        .bcp-brand img {
            width: 65px;
            height: 65px;
        }

        .bcp-brand-divider {
            height: 50px;
        }

        .bcp-brand-name {
            font-size: 17px;
        }

        .bcp-brand-name small {
            font-size: 9px;
            letter-spacing: 1px;
        }

        .bcp-brand-motto {
            font-size: 12px;
        }

        .bcp-job-main {
            padding: 22px 18px;
        }

        .bcp-job-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .bcp-hiring-title {
            font-size: 45px;
        }

        .bcp-job-footer {
            grid-template-columns: 1fr;
            margin: 0 12px 12px;
            padding: 18px;
        }

        .bcp-join-message {
            display: none;
        }
    }

    @media (max-width: 420px) {
        .bcp-brand img {
            width: 48px;
            height: 48px;
        }

        .bcp-brand-name {
            font-size: 14px;
        }

        .bcp-brand-name small {
            font-size: 8px;
        }

        .bcp-brand-motto {
            display: none;
        }

        .bcp-company-logo {
            width: 70px;
            height: 65px;
        }

        .bcp-company-name {
            font-size: 16px;
        }
    }
</style>

<section class="bcp-jobs" id="bcpJobCarousel">

    <!-- BCP INSTITUTION HEADER -->
    <div class="bcp-jobs-header">

        <div class="bcp-brand">

            <img
                src="<?= asset('assets/images/bcp-logo.png') ?>"
                alt="Best Link College of the Philippines Logo"
            >

            <div class="bcp-brand-divider"></div>

            <div class="bcp-brand-name">
                BCP
                <small>BEST LINK COLLEGE<br>OF THE PHILIPPINES</small>
            </div>

        </div>

        <div class="bcp-brand-motto">
            Building Better Futures Together
        </div>

    </div>
<?php
$jobPostings = (isset($jobPostings) && is_array($jobPostings))
    ? $jobPostings
    : [];
?>

<?php if (!empty($jobPostings)): ?>

    <?php
    $decodeItems = static function ($value) {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Fallback for regular multiline text
        return preg_split(
            '/\r\n|\r|\n/',
            trim((string) $value),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];
    };
    ?>

    <div class="bcp-job-inner">

        <?php foreach ($jobPostings as $index => $job): ?>

            <?php
            $companyName = $job['comp_name'] ?? 'Company Name';

            $logo = !empty($job['company_logo'])
                ? asset(ltrim($job['company_logo'], '/'))
                : '';

            $decodeItems = static function ($value) {
                if (is_array($value)) {
                    return $value;
                }

                $decoded = json_decode((string) $value, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }

                return preg_split(
                    '/\r\n|\r|\n/',
                    trim((string) $value),
                    -1,
                    PREG_SPLIT_NO_EMPTY
                ) ?: [];
            };

            $qualifications = $decodeItems($job['qualifications'] ?? '');
            $benefits = $decodeItems($job['benefits'] ?? '');
            $positions = $decodeItems($job['open_positions'] ?? '');
            ?>

            <article
                class="bcp-job-slide <?= $index === 0 ? 'active' : '' ?>"
                aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>"
            >

                <div class="bcp-job-main">

                    <!-- COMPANY INFORMATION -->
                    <div class="bcp-company">

                        <div class="bcp-company-logo">

                            <?php if ($logo): ?>

                                <img
                                    src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?> Logo"
                                >

                            <?php else: ?>

                                <img
                                    src="<?= asset('assets/images/bcp-logo.png') ?>"
                                    alt="BCP Logo"
                                >

                            <?php endif; ?>

                        </div>

                        <div>
                            <div class="bcp-company-name">
                                <?= htmlspecialchars(strtoupper($companyName), ENT_QUOTES, 'UTF-8') ?>
                            </div>

                            <div class="bcp-company-subtitle">
                                Education &nbsp; | &nbsp; Excellence &nbsp; | &nbsp; Service
                            </div>
                        </div>

                    </div>

                    <!-- JOB DETAILS -->
                    <div class="bcp-job-grid">

                        <div>

                            <h1 class="bcp-hiring-title" style="font-size: 90px;">
                                We are<br>
                                <span>Hiring!</span>
                            </h1>

                            <!-- QUALIFICATIONS -->
                            <div class="bcp-job-group">

                                <h3 class="bcp-job-heading">
                                    <i class="fas fa-graduation-cap"></i>
                                    Qualifications
                                </h3>

                                <ul class="bcp-job-list">

                                    <?php if (!empty($qualifications)): ?>

                                        <?php foreach ($qualifications as $item): ?>
                                            <li>
                                                <?= htmlspecialchars(
                                                    is_array($item)
                                                        ? implode(', ', array_map('strval', $item))
                                                        : trim((string) $item),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </li>
                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <li>No qualifications specified.</li>

                                    <?php endif; ?>

                                </ul>

                            </div>

                            <!-- BENEFITS -->
                            <div class="bcp-job-group">

                                <h3 class="bcp-job-heading">
                                    <i class="fas fa-gift"></i>
                                    Benefits
                                </h3>

                                <ul class="bcp-job-list">

                                    <?php if (!empty($benefits)): ?>

                                        <?php foreach ($benefits as $item): ?>
                                            <li>
                                                <?= htmlspecialchars(
                                                    is_array($item)
                                                        ? implode(', ', array_map('strval', $item))
                                                        : trim((string) $item),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </li>
                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <li>No benefits specified.</li>

                                    <?php endif; ?>

                                </ul>

                            </div>

                        </div>

                        <!-- OPEN POSITIONS -->
                        <div class="bcp-positions">

                            <h3 class="bcp-positions-heading">
                                <i class="fas fa-briefcase"></i>
                                Open Positions
                            </h3>

                            <div class="bcp-position-list">

                                <?php if (!empty($positions)): ?>

                                    <?php foreach ($positions as $position): ?>

                                        <?php
                                        $positionName = '';
                                        $employeeCount = 0;

                                        if (is_array($position)) {
                                            $positionName = $position['position']
                                                ?? $position['title']
                                                ?? $position['name']
                                                ?? '';

                                            $employeeCount = $position['quantity']
                                                ?? $position['count']
                                                ?? $position['vacancies']
                                                ?? 0;
                                        } else {
                                            $positionName = $position;
                                        }
                                        ?>

                                        <div class="bcp-position-item">

                                            <span class="bcp-position-name">
                                                <?= htmlspecialchars(
                                                    trim((string) $positionName),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </span>

                                            <span class="bcp-position-count">
                                                <?= (int) $employeeCount ?> needed
                                            </span>

                                        </div>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <div class="bcp-no-position">
                                        No open positions specified.
                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- CONTACT FOOTER -->
                <div class="bcp-job-footer">

                    <div class="bcp-contact">

                        <div class="bcp-contact-icon">
                            <i class="fas fa-paper-plane"></i>
                        </div>

                        <div>
                            <strong class="bcp-contact-title">
                                Send Your CV
                            </strong>

                            <span class="bcp-contact-info">
                                <i class="fas fa-envelope"></i>
                                <?= htmlspecialchars($job['comp_email'] ?? 'Email not provided', ENT_QUOTES, 'UTF-8') ?>
                            </span>

                            <span class="bcp-contact-info">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($job['comp_address'] ?? 'Address not provided', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                    </div>

                    <div class="bcp-contact">

                        <div class="bcp-contact-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>

                        <div>
                            <strong class="bcp-contact-title">
                                Contact Us
                            </strong>

                            <span class="bcp-contact-info">
                                <i class="fas fa-phone"></i>
                                <?= htmlspecialchars($job['comp_number'] ?? 'Contact number not provided', ENT_QUOTES, 'UTF-8') ?>
                            </span>

                            <span class="bcp-contact-info">
                                <i class="fas fa-envelope"></i>
                                <?= htmlspecialchars($job['comp_email'] ?? 'Email not provided', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                    </div>

                    <div class="bcp-join-message">
                        Join Our<br>Team!
                    </div>

                </div>

            </article>

        <?php endforeach; ?>

        <!-- DOT PAGINATION -->
        <?php if (count($jobPostings) > 1): ?>

            <div class="bcp-job-pagination">

                <?php foreach ($jobPostings as $index => $job): ?>

                    <button
                        type="button"
                        class="bcp-job-dot <?= $index === 0 ? 'active' : '' ?>"
                        data-index="<?= $index ?>"
                        aria-label="Show job posting <?= $index + 1 ?>"
                        aria-current="<?= $index === 0 ? 'true' : 'false' ?>"
                    ></button>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

<?php else: ?>

    <!-- EMPTY STATE -->
    <div class="bcp-job-inner">

        <div class="bcp-job-empty">

            <i class="fas fa-briefcase"></i>

            <h2 style="color:#10265c; font-size:20px; margin:15px 0 8px;">
                No Job Openings Available
            </h2>

            <p style="font-size:13px;">
                There are currently no available job postings.
                Please check again later.
            </p>

        </div>

    </div>

<?php endif; ?>

</section>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const carousel = document.getElementById('bcpJobCarousel');

    if (!carousel) return;

    const slides = carousel.querySelectorAll('.bcp-job-slide');
    const dots = carousel.querySelectorAll('.bcp-job-dot');

    if (slides.length <= 1) return;

    let currentIndex = 0;
    let autoPlay = null;

    function showSlide(index) {

        currentIndex = index;

        slides.forEach((slide, i) => {

            const isActive = i === index;

            slide.classList.toggle('active', isActive);
            slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');

        });

        dots.forEach((dot, i) => {

            const isActive = i === index;

            dot.classList.toggle('active', isActive);
            dot.setAttribute('aria-current', isActive ? 'true' : 'false');

        });

    }

    function nextSlide() {

        const nextIndex = (currentIndex + 1) % slides.length;

        showSlide(nextIndex);

    }

    function startAutoPlay() {

        clearInterval(autoPlay);

        autoPlay = setInterval(nextSlide, 5000);

    }

    // DOT CLICK
    dots.forEach(dot => {

        dot.addEventListener('click', function () {

            const index = Number(this.dataset.index);

            if (Number.isInteger(index) && index >= 0 && index < slides.length) {

                showSlide(index);
                startAutoPlay();

            }

        });

    });

    // PAUSE WHILE HOVERING
    carousel.addEventListener('mouseenter', function () {
        clearInterval(autoPlay);
    });

    carousel.addEventListener('mouseleave', function () {
        startAutoPlay();
    });

    // INITIALIZE
    showSlide(0);
    startAutoPlay();

});
</script>
