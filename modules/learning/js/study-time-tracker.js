(function() {
    'use strict';

    var activeCourseId = 0;
    var lastTickAt = 0;
    var sequence = 0;
    var sessionToken = '';
    var intervalId = null;

    function readCourseId(pageName) {
        var params = new URLSearchParams(window.location.search);
        var page = pageName || params.get('page') || '';
        if (page.indexOf('learner/study-subpage/') !== 0) return 0;
        return parseInt(params.get('course_id') || '0', 10) || 0;
    }

    function newSessionToken() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(character) {
            var random = Math.random() * 16 | 0;
            return (character === 'x' ? random : (random & 3 | 8)).toString(16);
        });
    }

    function sendSeconds(seconds) {
        if (seconds <= 0 || !activeCourseId) return;
        sequence++;
        fetch('pages/learner/ajax/record-study-time.php', {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': window.CSRF_TOKEN || ''
            },
            body: JSON.stringify({
                course_id: activeCourseId,
                session_token: sessionToken,
                sequence: sequence,
                seconds: Math.min(60, seconds)
            })
        }).catch(function() {});
    }

    function flushVisibleTime() {
        var now = Date.now();
        if (document.visibilityState === 'visible' && lastTickAt > 0) {
            var elapsedSeconds = Math.floor((now - lastTickAt) / 1000);
            if (elapsedSeconds > 0) sendSeconds(elapsedSeconds);
        }
        lastTickAt = document.visibilityState === 'visible' ? now : 0;
    }

    function stopTracking() {
        if (intervalId !== null) window.clearInterval(intervalId);
        intervalId = null;
        flushVisibleTime();
        activeCourseId = 0;
        lastTickAt = 0;
    }

    function syncTracking(pageName) {
        var courseId = readCourseId(pageName);
        if (courseId === activeCourseId && intervalId !== null) return;
        stopTracking();
        if (!courseId) return;

        activeCourseId = courseId;
        sessionToken = newSessionToken();
        sequence = 0;
        lastTickAt = document.visibilityState === 'visible' ? Date.now() : 0;
        intervalId = window.setInterval(flushVisibleTime, 30000);
    }

    document.addEventListener('visibilitychange', flushVisibleTime);
    window.addEventListener('pagehide', stopTracking);
    window.addEventListener('pageshow', function() { syncTracking(); });
    window.addEventListener('page:loaded', function(event) {
        syncTracking(event.detail && event.detail.page ? event.detail.page : '');
    });
    syncTracking();
})();