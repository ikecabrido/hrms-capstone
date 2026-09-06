<header>
    <div id="hamburgerContainer" style="
    position:relative;
    display:inline-block;
">

        <!-- HAMBURGER BUTTON -->
        <button type="button" onclick="toggleSidebar()" style="
            width:60px;
            height:38px;
            display:flex;
            align-items:center;
            justify-content:center;
            border:1px solid #e5e7eb;
            border-radius:9px;
            background:#fff;
            cursor:pointer;
        ">
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </button>

    </div>



    <div class="realtime" style="font-size: 16px" id="realtimeClock">--:--</div>
    <script>
        (function () {
            function updateClock() {
                const clock = document.getElementById('realtimeClock');
                if (!clock) return;

                const now = new Date();

                let hours = now.getHours();
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');

                const ampm = hours >= 12 ? 'PM' : 'AM';

                hours = hours % 12 || 12;

                clock.textContent =
                    String(hours).padStart(2, '0') +
                    ':' + minutes +
                    ' ' + ampm;
            }

            updateClock();
            setInterval(updateClock, 1000);
        })();
    </script>
</header>