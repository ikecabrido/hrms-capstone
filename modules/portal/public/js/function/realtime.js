(function () {
    function updateClock() {
        var clock = document.getElementById('realtimeClock');

        if (!clock) {
            console.log('Clock element not found');
            return;
        }

        var now = new Date();

        var hours = now.getHours();
        var minutes = now.getMinutes();
        var seconds = now.getSeconds();

        var ampm = hours >= 12 ? 'PM' : 'AM';

        hours = hours % 12;
        hours = hours ? hours : 12;

        hours = String(hours).padStart(2, '0');
        minutes = String(minutes).padStart(2, '0');
        seconds = String(seconds).padStart(2, '0');

        clock.textContent =
            hours + ':' + minutes + ':' + seconds + ' ' + ampm;
    }

    updateClock();
    setInterval(updateClock, 1000);
})();