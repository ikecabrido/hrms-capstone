document.addEventListener('DOMContentLoaded', function () {

    /* =====================================================
       REAL-TIME CLOCK
       ===================================================== */

    const clock = document.getElementById('realtimeClock');

    function updateClock() {

        if (!clock) {
            return;
        }

        const now = new Date();

        const hours = now.getHours();
        const minutes = now.getMinutes();

        const period = hours >= 12 ? 'pm' : 'am';

        const displayHours = hours % 12 || 12;
        const displayMinutes = String(minutes).padStart(2, '0');

        clock.textContent =
            `${displayHours}:${displayMinutes} ${period}`;
    }

    updateClock();

    setInterval(updateClock, 1000);


    /* =====================================================
       EMPLOYEE BANNER
       ===================================================== */

    const banner = document.getElementById('employeeBanner');

    if (banner) {

        function changeBannerImage() {

            const randomNumber =
                Math.floor(Math.random() * 999999);

            banner.src =
                `https://loremflickr.com/1200/350/employee,office,teamwork?lock=${randomNumber}`;
        }

        changeBannerImage();

        setInterval(changeBannerImage, 5000);
    }


    /* =====================================================
       WELCOME ANIMATION
       ===================================================== */

    const welcome = document.getElementById('dashboardWelcome');

    if (welcome && typeof anime !== 'undefined') {

        anime.timeline({
            easing: 'easeOutExpo'
        })

        .add({
            targets: '#dashboardWelcome',
            opacity: [0, 1],
            translateY: [20, 0],
            duration: 700
        })

        .add({
            targets: '#welcomeLabel',
            opacity: [0, 1],
            translateX: [-15, 0],
            duration: 500
        }, '-=400')

        .add({
            targets: '#welcomeTitle',
            opacity: [0, 1],
            translateX: [-20, 0],
            duration: 600
        }, '-=350')

        .add({
            targets: '#welcomeDescription',
            opacity: [0, 1],
            translateY: [10, 0],
            duration: 500
        }, '-=350')

        .add({
            targets: '#welcomeDecoration',
            opacity: [0, 1],
            scale: [0.7, 1],
            rotate: [-10, 0],
            duration: 700
        }, '-=400')

        .add({
            targets: '.welcome-line',
            width: ['0%', '70px'],
            duration: 500
        }, '-=300');


        /* Floating icon */
        anime({
            targets: '#welcomeDecoration',
            translateY: [-5, 5],
            duration: 2200,
            direction: 'alternate',
            loop: true,
            easing: 'easeInOutSine'
        });


        /* Background glow */
        anime({
            targets: '.glow-one',
            translateX: [-20, 20],
            translateY: [-10, 10],
            duration: 3500,
            direction: 'alternate',
            loop: true,
            easing: 'easeInOutSine'
        });


        /* =================================================
           HOVER ANIMATION
           ================================================= */

        welcome.addEventListener('mouseenter', function () {

            anime({
                targets: '#welcomeDecoration',
                translateY: -10,
                rotate: 8,
                scale: 1.10,
                duration: 500,
                easing: 'easeOutElastic(1, .5)'
            });

            anime({
                targets: '.glow-one',
                translateX: -30,
                translateY: 20,
                scale: 1.25,
                opacity: 1,
                duration: 800,
                easing: 'easeOutQuad'
            });

            anime({
                targets: '.glow-two',
                translateX: 30,
                translateY: -15,
                scale: 1.20,
                opacity: 0.95,
                duration: 800,
                easing: 'easeOutQuad'
            });

        });


        welcome.addEventListener('mouseleave', function () {

            anime({
                targets: '#welcomeDecoration',
                translateY: 0,
                rotate: 0,
                scale: 1,
                duration: 500,
                easing: 'easeOutElastic(1, .5)'
            });

            anime({
                targets: '.glow-one',
                translateX: 0,
                translateY: 0,
                scale: 1,
                opacity: 0.8,
                duration: 700,
                easing: 'easeOutQuad'
            });

            anime({
                targets: '.glow-two',
                translateX: 0,
                translateY: 0,
                scale: 1,
                opacity: 0.7,
                duration: 700,
                easing: 'easeOutQuad'
            });

        });

    }

});