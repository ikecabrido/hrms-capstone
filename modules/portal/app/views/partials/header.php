<header>
    <div id="hamburgerContainer" style="
    position:relative;
    display:inline-block;
">

        <!-- HAMBURGER BUTTON -->
        <button type="button" onclick="toggleHamburgerMenu(event)" style="
        width: 60px;
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

        <!-- DROPDOWN -->
        <div id="hamburgerDropdown" style="
        display:none;
        position:absolute;
        left:calc(100% + 10px);
        top:0;
        width:185px;
        padding:6px;
        background:#fff;
        border:1px solid #e5e7eb;
        border-radius:12px;
        box-shadow:0 12px 30px rgba(15,23,42,.12);
        z-index:99999;
        box-sizing:border-box;
    ">

            <!-- HEADER -->
            <div style="
            padding:9px 10px 8px;
            border-bottom:1px solid #f1f5f9;
            margin-bottom:4px;
        ">
                <div style="
                color:#111827;
                font-size:10px;
                font-weight:700;
            ">
                    Account
                </div>

                <div style="
                margin-top:2px;
                color:#9ca3af;
                font-size:8px;
            ">
                    Manage your account
                </div>
            </div>

            <!-- SETTINGS -->
            <a href="#" style="
            display:flex;
            align-items:center;
            gap:10px;
            width:100%;
            padding:9px 10px;
            border-radius:8px;
            color:#374151;
            background:transparent;
            font-size:10px;
            font-weight:600;
            text-decoration:none;
            box-sizing:border-box;
        " onmouseover="
            this.style.background='#f8fafc';
            this.style.color='#2563eb';
        " onmouseout="
            this.style.background='transparent';
            this.style.color='#374151';
        ">

                <span style="
                width:28px;
                height:28px;
                display:flex;
                align-items:center;
                justify-content:center;
                flex-shrink:0;
                border-radius:7px;
                background:#eff6ff;
                color:#2563eb;
            ">
                    <i class="fa-solid fa-gear" style="font-size:11px;"></i>
                </span>

                <span>Settings</span>

            </a>

            <!-- SIGN OUT -->
            <a href="/hrms-capstone/modules/portal/index.php?url=auth-logout" style="
            display:flex;
            align-items:center;
            gap:10px;
            width:100%;
            padding:9px 10px;
            border-radius:8px;
            color:#dc2626;
            background:transparent;
            font-size:10px;
            font-weight:600;
            text-decoration:none;
            box-sizing:border-box;
        " onmouseover="
            this.style.background='#fef2f2';
            this.style.color='#b91c1c';
        " onmouseout="
            this.style.background='transparent';
            this.style.color='#dc2626';
        ">

                <span style="
                width:28px;
                height:28px;
                display:flex;
                align-items:center;
                justify-content:center;
                flex-shrink:0;
                border-radius:7px;
                background:#fef2f2;
                color:#dc2626;
            ">
                    <i class="fa-solid fa-right-from-bracket" style="font-size:11px;"></i>
                </span>

                <span>Sign Out</span>

            </a>

        </div>

    </div>

    <script>
        function toggleHamburgerMenu(event) {
            event.stopPropagation();

            const menu = document.getElementById('hamburgerDropdown');

            menu.style.display =
                menu.style.display === 'block' ? 'none' : 'block';
        }

        document.addEventListener('click', function (event) {

            const container = document.getElementById('hamburgerContainer');
            const menu = document.getElementById('hamburgerDropdown');

            if (!container.contains(event.target)) {
                menu.style.display = 'none';
            }

        });
    </script>

    <div class="realtime" id="realtimeClock">--:--</div>
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