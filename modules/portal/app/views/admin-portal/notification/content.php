<div class="employee-dashboard">
    <section class="dashboard-welcome" id="dashboardWelcome">


        <div class="welcome-glow glow-one"></div>
        <div class="welcome-glow glow-two"></div>

        <div class="welcome-content">

            <span class="welcome-label" id="welcomeLabel">
                <i class="fas fa-circle"></i>
                ADMIN NOTIFICATION MANAGEMENT
            </span>

            <h1 id="welcomeTitle">
                Notifications
            </h1>

            <p id="welcomeDescription">
                Create, manage, and monitor system notifications, employee alerts,
                important reminders, and administrative messages to ensure timely
                communication across the organization.
            </p>

            <div class="welcome-line"></div>

        </div>

        <div class="welcome-decoration" id="welcomeDecoration">
            <i class="fas fa-bell"></i>
        </div>


    </section>
    <?php require __DIR__ . '/../../partials/notification.php'; ?>

<section id="dashboardWelcomeSection" class="w-full min-h-0 bg-slate-50 p-3 sm:p-4 lg:p-5">
    <h1 class="responsive-notification">
        <span style="--i:1">N</span><span style="--i:2">O</span><span style="--i:3">T</span><span style="--i:4">I</span><span style="--i:5">F</span><span style="--i:6">I</span><span style="--i:7">C</span><span style="--i:8">A</span><span style="--i:9">T</span><span style="--i:10">I</span><span style="--i:11">O</span><span style="--i:12">N</span><span style="--i:13">S</span>
    </h1>
</section>

</div>
<style>
    .responsive-notification {
    font-size: clamp(2.5rem, 12vw, 200px);
    text-align: center;
    line-height: 1.1;
    font-family: 'Arial Black', sans-serif;
    overflow: hidden;
    display: flex;
    justify-content: center;
    /* Optional: Animate the whole word's letter spacing at the end */
    animation: wordPulse 5s ease-in-out infinite;
}

.responsive-notification span {
    display: inline-block;
    opacity: 0;
    transform: translateY(20px) scale(0.5);
    /* Calculates staggered delay: Letter 1 starts at 0.1s, Letter 2 at 0.2s, etc. */
    animation: letterReveal 5s cubic-bezier(0.16, 1, 0.3, 1) infinite;
    animation-delay: calc(var(--i) * 0.1s);
}

/* --- ANIMATION TIMELINES --- */

@keyframes letterReveal {
    0% {
        opacity: 0;
        transform: translateY(20px) scale(0.5);
    }
    /* 10% to 70%: Letters are fully visible and resting on screen */
    10%, 70% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    /* 80% to 100%: Letters fade out upwards to reset the repetitive loop */
    80%, 100% {
        opacity: 0;
        transform: translateY(-20px) scale(1);
    }
}

@keyframes wordPulse {
    /* Subtle pop/pulse effect right after all letters finish appearing (around 30% mark) */
    25% {
        transform: scale(1);
        letter-spacing: 0px;
    }
    30% {
        transform: scale(1.03);
        letter-spacing: 2px;
    }
    35% {
        transform: scale(1);
        letter-spacing: 0px;
    }
}

</style>