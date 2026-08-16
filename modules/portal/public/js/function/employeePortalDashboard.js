    document.addEventListener('DOMContentLoaded', () => {

        const dashboard = document.querySelector('.employee-dashboard');

        if (!dashboard || typeof anime === 'undefined') return;

        const reduceMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)'
        ).matches;

        /* =========================================================
           HELPERS
        ========================================================= */

        const animate = (targets, options) => {
            if (reduceMotion) return;

            return anime({
                targets,
                ...options
            });
        };

        /* =========================================================
           INITIAL STATE
        ========================================================= */

        const welcome = document.querySelector('#dashboardWelcome');
        const label = document.querySelector('#welcomeLabel');
        const title = document.querySelector('#welcomeTitle');
        const description = document.querySelector('#welcomeDescription');
        const line = document.querySelector('.welcome-line');
        const decoration = document.querySelector('#welcomeDecoration');

        const sections = document.querySelectorAll('.dashboard-section');

        /* =========================================================
           PAGE ENTRANCE
        ========================================================= */

        if (!reduceMotion) {

            anime.set(welcome, {
                opacity: 0,
                translateY: 25
            });

            anime.set(
                [label, title, description, line],
                {
                    opacity: 0,
                    translateY: 12
                }
            );

            anime.set(decoration, {
                opacity: 0,
                scale: .65,
                rotate: -20
            });

            anime.set(sections, {
                opacity: 0,
                translateY: 25
            });

            const intro = anime.timeline({
                easing: 'easeOutExpo'
            });

            intro
                .add({
                    targets: welcome,
                    opacity: [0, 1],
                    translateY: [25, 0],
                    duration: 750
                })
                .add({
                    targets: label,
                    opacity: [0, 1],
                    translateY: [12, 0],
                    duration: 400
                }, '-=450')
                .add({
                    targets: title,
                    opacity: [0, 1],
                    translateY: [12, 0],
                    duration: 500
                }, '-=320')
                .add({
                    targets: description,
                    opacity: [0, 1],
                    translateY: [12, 0],
                    duration: 450
                }, '-=330')
                .add({
                    targets: line,
                    opacity: [0, 1],
                    scaleX: [0, 1],
                    transformOrigin: 'left center',
                    duration: 450
                }, '-=300')
                .add({
                    targets: decoration,
                    opacity: [0, 1],
                    scale: [.65, 1],
                    rotate: [-20, 0],
                    duration: 850,
                    easing: 'easeOutElastic(1,.6)'
                }, '-=500')
                .add({
                    targets: sections,
                    opacity: [0, 1],
                    translateY: [25, 0],
                    delay: anime.stagger(120),
                    duration: 550,
                    easing: 'easeOutCubic'
                }, '-=500');

        } else {

            welcome.style.opacity = '1';
            sections.forEach(section => {
                section.style.opacity = '1';
                section.style.transform = 'none';
            });
        }

        /* =========================================================
           FLOATING WELCOME ICON
        ========================================================= */

        animate(decoration, {
            translateY: ['-50%', 'calc(-50% - 9px)'],
            direction: 'alternate',
            loop: true,
            duration: 2200,
            easing: 'easeInOutSine'
        });

        /* =========================================================
           ANIMATED BACKGROUND GLOWS
        ========================================================= */

        animate('.glow-one', {
            translateX: [-35, 35],
            translateY: [15, -20],
            scale: [.9, 1.12],
            direction: 'alternate',
            loop: true,
            duration: 4800,
            easing: 'easeInOutSine'
        });

        animate('.glow-two', {
            translateX: [20, -25],
            translateY: [-10, 20],
            scale: [1, 1.2],
            direction: 'alternate',
            loop: true,
            duration: 3600,
            easing: 'easeInOutSine'
        });

        /* =========================================================
           WELCOME MOUSE PARALLAX
        ========================================================= */

        if (!reduceMotion && window.innerWidth > 700) {

            dashboard.addEventListener('mousemove', e => {

                const rect = dashboard.getBoundingClientRect();

                const x = (e.clientX - rect.left) / rect.width - .5;
                const y = (e.clientY - rect.top) / rect.height - .5;

                anime.remove(decoration);

                anime({
                    targets: decoration,
                    translateX: x * 15,
                    translateY: `calc(-50% + ${y * 12}px)`,
                    rotateY: x * 8,
                    rotateX: y * -8,
                    duration: 700,
                    easing: 'easeOutCubic'
                });
            });

            dashboard.addEventListener('mouseleave', () => {

                anime({
                    targets: decoration,
                    translateX: 0,
                    translateY: '-50%',
                    rotateX: 0,
                    rotateY: 0,
                    duration: 800,
                    easing: 'easeOutElastic(1,.7)'
                });
            });
        }

        /* =========================================================
           SERVICE CARD 3D INTERACTION
        ========================================================= */

        const interactiveCards = document.querySelectorAll(
            '.dashboard-card, .request-card'
        );

        interactiveCards.forEach(card => {

            card.addEventListener('mousemove', e => {

                if (reduceMotion) return;

                const rect = card.getBoundingClientRect();

                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const percentX = x / rect.width;
                const percentY = y / rect.height;

                const rotateX = (percentY - .5) * -7;
                const rotateY = (percentX - .5) * 7;

                card.style.setProperty(
                    '--mouse-x',
                    `${percentX * 100}%`
                );

                card.style.setProperty(
                    '--mouse-y',
                    `${percentY * 100}%`
                );

                anime.remove(card);

                anime({
                    targets: card,
                    translateY: -5,
                    rotateX,
                    rotateY,
                    scale: 1.015,
                    duration: 180,
                    easing: 'easeOutQuad'
                });
            });

            card.addEventListener('mouseenter', () => {

                if (reduceMotion) return;

                const icon = card.querySelector(
                    '.dashboard-card-icon, .request-card > i'
                );

                if (icon) {

                    anime.remove(icon);

                    anime({
                        targets: icon,
                        scale: [1, 1.16, 1.05],
                        rotate: [0, -8, 6, 0],
                        duration: 500,
                        easing: 'easeOutElastic(1,.7)'
                    });
                }
            });

            card.addEventListener('mouseleave', () => {

                if (reduceMotion) return;

                anime.remove(card);

                anime({
                    targets: card,
                    translateY: 0,
                    rotateX: 0,
                    rotateY: 0,
                    scale: 1,
                    duration: 700,
                    easing: 'easeOutElastic(1,.7)'
                });
            });
        });

        /* =========================================================
           ANNOUNCEMENT HOVER
        ========================================================= */

        const announcements = document.querySelectorAll(
            'a[href*="announcement-view"]'
        );

        announcements.forEach(item => {

            item.addEventListener('mouseenter', () => {

                if (reduceMotion) return;

                const icon = item.querySelector('.fas.fa-bullhorn');
                const arrow = item.querySelector('.fa-chevron-right');

                if (icon) {

                    anime({
                        targets: icon,
                        rotate: [-5, 5, 0],
                        scale: [1, 1.12, 1],
                        duration: 400,
                        easing: 'easeOutQuad'
                    });
                }

                if (arrow) {

                    anime({
                        targets: arrow,
                        translateX: [0, 5],
                        duration: 300,
                        easing: 'easeOutQuad'
                    });
                }
            });

            item.addEventListener('mouseleave', () => {

                if (reduceMotion) return;

                const arrow = item.querySelector('.fa-chevron-right');

                if (arrow) {

                    anime({
                        targets: arrow,
                        translateX: 0,
                        duration: 250,
                        easing: 'easeOutQuad'
                    });
                }
            });
        });

        /* =========================================================
           SECTION HEADER MICRO ANIMATION
        ========================================================= */

        document.querySelectorAll(
            '.dashboard-section-header span'
        ).forEach(label => {

            label.addEventListener('mouseenter', () => {

                if (reduceMotion) return;

                anime({
                    targets: label,
                    translateX: [0, 4, 0],
                    duration: 400,
                    easing: 'easeOutQuad'
                });
            });
        });

        /* =========================================================
           CARD ICON IDLE MOTION
        ========================================================= */

        if (!reduceMotion) {

            document.querySelectorAll(
                '.dashboard-card-icon'
            ).forEach((icon, index) => {

                anime({
                    targets: icon,
                    translateY: [-1.5, 1.5],
                    direction: 'alternate',
                    loop: true,
                    duration: 1800 + (index * 120),
                    delay: index * 1200,
                    easing: 'easeInOutSine'
                });
            });
        }

        /* =========================================================
           REQUEST ICON IDLE MOTION
        ========================================================= */

        if (!reduceMotion) {

            document.querySelectorAll(
                '.request-card > i'
            ).forEach((icon, index) => {

                anime({
                    targets: icon,
                    translateY: [-1, 1],
                    direction: 'alternate',
                    loop: true,
                    duration: 2200 + (index * 200),
                    delay: index * 500,
                    easing: 'easeInOutSine'
                });
            });
        }

        /* =========================================================
           VIEW ALL BUTTON
        ========================================================= */

        const viewAll = document.querySelector(
            '.dashboard-section-header a[href*="announcement"]'
        );

        if (viewAll && !reduceMotion) {

            viewAll.addEventListener('mouseenter', () => {

                anime({
                    targets: viewAll.querySelector('i'),
                    translateX: [0, 5],
                    duration: 350,
                    easing: 'easeOutQuad'
                });
            });
        }

        /* =========================================================
           KEYBOARD ACCESSIBILITY
        ========================================================= */

        interactiveCards.forEach(card => {

            card.addEventListener('focus', () => {

                if (reduceMotion) return;

                anime({
                    targets: card,
                    translateY: -3,
                    scale: 1.01,
                    duration: 350,
                    easing: 'easeOutCubic'
                });
            });

            card.addEventListener('blur', () => {

                if (reduceMotion) return;

                anime({
                    targets: card,
                    translateY: 0,
                    scale: 1,
                    rotateX: 0,
                    rotateY: 0,
                    duration: 400,
                    easing: 'easeOutCubic'
                });
            });
        });

    });

    document.addEventListener('DOMContentLoaded', () => {

        const dashboard = document.querySelector('.employee-dashboard');
        const tracer = document.querySelector('.cursor-tracer');

        if (!dashboard || !tracer) return;

        let mouseX = 0;
        let mouseY = 0;

        let currentX = 0;
        let currentY = 0;

        let visible = false;

        /* ---------------------------------------------
           Mouse tracking
        --------------------------------------------- */

        document.addEventListener('mousemove', e => {

            mouseX = e.clientX;
            mouseY = e.clientY;

            dashboard.style.setProperty('--x', `${mouseX}px`);
            dashboard.style.setProperty('--y', `${mouseY}px`);

            if (!visible) {
                visible = true;
                tracer.style.opacity = '1';
            }

        });


        /* ---------------------------------------------
           Smooth tracer movement
        --------------------------------------------- */

        function animateTracer() {

            currentX += (mouseX - currentX) * 0.12;
            currentY += (mouseY - currentY) * 0.12;

            tracer.style.transform =
                `translate3d(${currentX - 130}px,
                          ${currentY - 130}px,
                          0)`;

            requestAnimationFrame(animateTracer);
        }

        animateTracer();


        /* ---------------------------------------------
           Hide tracer outside dashboard
        --------------------------------------------- */

        dashboard.addEventListener('mouseenter', () => {
            tracer.style.opacity = '1';
        });

        dashboard.addEventListener('mouseleave', () => {
            tracer.style.opacity = '0';
        });


        /* ---------------------------------------------
           Hide tracer when entering modules
        --------------------------------------------- */

        const modules = document.querySelectorAll(`
        .dashboard-card,
        .request-card,
        .dashboard-section a[href*="announcement-view"],
        .dashboard-welcome
    `);

        modules.forEach(module => {

            module.addEventListener('mouseenter', () => {

                tracer.style.opacity = '0';

            });

            module.addEventListener('mouseleave', () => {

                tracer.style.opacity = '1';

            });

        });

    });